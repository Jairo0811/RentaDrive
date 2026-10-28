<?php

declare(strict_types=1);

namespace App\Domain\Automation\Services;

use App\Models\AutomationDelivery;
use App\Models\Company;
use App\Models\Reservation;
use App\Models\Rental;
use App\Models\VehicleMaintenance;
use App\Support\Tenancy\TenantContext;
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
            } finally {
                $tenant->clear();
            }
        }

        return $count;
    }

    private function deliver(Company $company, string $key, string $email, string $subject, string $body): bool
    {
        $existing = AutomationDelivery::query()->where('company_id', $company->getKey())
            ->where('event_key', $key)->where('recipient', $email)->where('channel', 'email')->first();

        if ($existing !== null) {
            return false;
        }

        $delivery = AutomationDelivery::query()->create([
            'company_id' => $company->getKey(),
            'event_key' => $key,
            'recipient' => $email,
            'channel' => 'email',
            'status' => 'queued',
        ]);

        try {
            Mail::raw($body, static function ($message) use ($email, $subject): void {
                $message->to($email)->subject('RentaDrive · '.$subject);
            });
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (Throwable $exception) {
            $delivery->update(['status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 500)]);
            Log::error('Fallo envío de automatización RentaDrive', ['delivery_id' => $delivery->getKey()]);

            return false;
        }

        return true;
    }
}
