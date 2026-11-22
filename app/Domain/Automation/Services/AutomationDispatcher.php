<?php

declare(strict_types=1);

namespace App\Domain\Automation\Services;

use App\Models\AutomationDelivery;
use App\Models\Company;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\VehicleMaintenance;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class AutomationDispatcher
{
    public function run(): int
    {
        if (! config('rentadrive.automation.enabled', false)) {
            return 0;
        }

        $count = 0;

        foreach (Company::query()->where('status', 'active')->cursor() as $company) {
            if (! filter_var($company->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $tenant = app(TenantContext::class);
            $tenant->set($company);

            try {
                if (Setting::query()->where('key', 'automation.email_opt_in')->value('value') !== '1') {
                    continue;
                }

                $starts = Reservation::query()->whereIn('status', ['pending', 'confirmed'])
                    ->whereDate('start_at', now()->addDay()->toDateString())->get();

                foreach ($starts as $reservation) {
                    $count += (int) $this->deliver(
                        $company, 'reservation-start:'.$reservation->getKey().':'.now()->toDateString(),
                        $company->email, 'Reserva '.$reservation->code,
                        'La reserva '.$reservation->code.' está prevista para mañana. Verifique la entrega.',
                    );
                }

                $overdue = Rental::query()->where('status', 'open')
                    ->where('expected_return_at', '<', now())->get();

                foreach ($overdue as $rental) {
                    $count += (int) $this->deliver(
                        $company, 'rental-overdue:'.$rental->getKey().':'.now()->toDateString(),
                        $company->email, 'Alquiler '.$rental->code.' pendiente',
                        'El alquiler '.$rental->code.' superó su fecha de devolución prevista.',
                    );
                }

                $maintenances = VehicleMaintenance::query()
                    ->where('status', 'scheduled')
                    ->whereDate('scheduled_at', '<=', now()->addDays(2)->toDateString())->get();

                foreach ($maintenances as $maintenance) {
                    $count += (int) $this->deliver(
                        $company, 'maintenance:'.$maintenance->getKey().':'.now()->toDateString(),
                        $company->email, 'Mantenimiento próximo',
                        'La orden de mantenimiento #'.$maintenance->getKey().' requiere atención.',
                    );
                }
            } catch (Throwable $exception) {
                // Un fallo en una empresa no debe impedir los avisos de las demás.
                Log::error('Fallo procesando automatizaciones de una empresa', [
                    'company_id' => $company->getKey(),
                    'exception' => $exception::class,
                ]);
            } finally {
                $tenant->clear();
            }
        }

        return $count;
    }

    /** Intentos máximos por aviso y retroceso (minutos × intentos ya realizados) entre reintentos. */
    private const MAX_ATTEMPTS = 3;

    private const BACKOFF_MINUTES = 15;

    private function deliver(Company $company, string $key, string $email, string $subject, string $body): bool
    {
        $delivery = $this->claim($company, $key, $email);

        if ($delivery === null) {
            return false;
        }

        $attempt = (int) $delivery->attempts + 1;
        $delivery->forceFill(['attempts' => $attempt, 'last_attempt_at' => now()])->save();

        try {
            Mail::raw($body, static function ($message) use ($email, $subject): void {
                $message->to($email)->subject('RentaDrive · '.$subject);
            });
            $delivery->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 500)]);
            Log::error('Fallo envío de automatización RentaDrive', [
                'delivery_id' => $delivery->getKey(),
                'attempt' => $attempt,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Reserva el derecho a enviar: crea el registro (único por empresa+evento+destinatario+canal) o reclama de forma
     * atómica uno fallido/atascado cuyo retroceso ya venció. Devuelve null si ya se envió, se agotaron los intentos,
     * aún no toca reintentar o lo está procesando otro proceso.
     */
    private function claim(Company $company, string $key, string $email): ?AutomationDelivery
    {
        $identity = [
            'company_id' => $company->getKey(),
            'event_key' => $key,
            'recipient' => $email,
            'channel' => 'email',
        ];

        try {
            return AutomationDelivery::query()->create([...$identity, 'status' => 'queued', 'attempts' => 0]);
        } catch (QueryException $exception) {
            $existing = AutomationDelivery::query()->where($identity)->first();

            if ($existing === null) {
                throw $exception; // No era una duplicidad: no se silencia.
            }
        }

        if ($existing->status === 'sent' || $existing->attempts >= self::MAX_ATTEMPTS) {
            return null;
        }

        $reference = $existing->last_attempt_at ?? $existing->created_at;

        if ($reference !== null && $reference->gt(now()->subMinutes(self::BACKOFF_MINUTES * max(1, (int) $existing->attempts)))) {
            return null;
        }

        $claimed = AutomationDelivery::query()
            ->whereKey($existing->getKey())
            ->where('attempts', $existing->attempts)
            ->update(['status' => 'queued', 'last_attempt_at' => now()]);

        return $claimed === 1 ? $existing->refresh() : null;
    }
}
