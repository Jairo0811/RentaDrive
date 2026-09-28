<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use App\Support\Automation\AutomationDispatchService;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ScanOperationalAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(
        TenantContext $tenant,
        AutomationDispatchService $dispatch,
    ): void {
        Company::query()
            ->whereIn('status', ['active', 'trial'])
            ->orderBy('id')
            ->chunkById(50, function ($companies) use ($tenant, $dispatch): void {
                foreach ($companies as $company) {
                    if (
                        $company->status === 'trial'
                        && $company->trial_ends_at !== null
                        && $company->trial_ends_at->isPast()
                    ) {
                        continue;
                    }

                    $tenant->set($company);

                    try {
                        $this->scanCompany($company, $dispatch);
                    } finally {
                        $tenant->clear();
                    }
                }
            });
    }

    private function scanCompany(
        Company $company,
        AutomationDispatchService $dispatch,
    ): void {
        $now = CarbonImmutable::now($company->timezone);
        $reservationHours = max(1, (int) $company->setting('automation.reservation_reminder_hours', 24));
        $returnHours = max(1, (int) $company->setting('automation.return_reminder_hours', 4));
        $maintenanceDays = max(1, (int) $company->setting('automation.maintenance_reminder_days', 7));
        $documentDays = max(1, (int) $company->setting('automation.document_reminder_days', 30));

        Reservation::query()
            ->with('customer')
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start_at', [$now, $now->addHours($reservationHours)])
            ->get()
            ->each(function (Reservation $reservation) use ($company, $dispatch): void {
                $title = 'Recordatorio de reserva '.$reservation->code;
                $message = sprintf(
                    'Tu reserva %s inicia el %s. Confirma que tendrás disponibles tu licencia y documentos requeridos.',
                    $reservation->code,
                    $reservation->start_at->format('d/m/Y h:i A'),
                );

                $this->queueCustomerChannels(
                    $company,
                    $dispatch,
                    'reservation-reminder:'.$reservation->getKey().':'.$reservation->start_at->timestamp,
                    Reservation::class,
                    (int) $reservation->getKey(),
                    $reservation->customer?->email,
                    $reservation->customer?->phone,
                    $title,
                    $message,
                );
            });

        Rental::query()
            ->with('customer')
            ->where('status', 'open')
            ->where('expected_return_at', '<=', $now->addHours($returnHours))
            ->get()
            ->each(function (Rental $rental) use ($company, $dispatch, $now): void {
                $overdue = $rental->expected_return_at->lt($now);
                $event = $overdue ? 'rental-overdue' : 'rental-return';
                $title = $overdue
                    ? 'Alquiler vencido '.$rental->code
                    : 'Recordatorio de devolución '.$rental->code;
                $message = $overdue
                    ? sprintf(
                        'El alquiler %s tenía devolución prevista para %s. Contacta al rent-a-car para coordinar la entrega.',
                        $rental->code,
                        $rental->expected_return_at->format('d/m/Y h:i A'),
                    )
                    : sprintf(
                        'El alquiler %s debe devolverse el %s. Recuerda combustible, accesorios y revisión de entrega.',
                        $rental->code,
                        $rental->expected_return_at->format('d/m/Y h:i A'),
                    );

                $this->queueCustomerChannels(
                    $company,
                    $dispatch,
                    $event.':'.$rental->getKey().':'.$rental->expected_return_at->timestamp,
                    Rental::class,
                    (int) $rental->getKey(),
                    $rental->customer?->email,
                    $rental->customer?->phone,
                    $title,
                    $message,
                );
            });

        VehicleMaintenance::query()
            ->with('vehicle.model.brand')
            ->where('status', 'scheduled')
            ->whereBetween('scheduled_at', [$now, $now->addDays($maintenanceDays)])
            ->get()
            ->each(function (VehicleMaintenance $maintenance) use ($company, $dispatch): void {
                $this->queueInternalChannels(
                    $company,
                    $dispatch,
                    'maintenance-date:'.$maintenance->getKey().':'.$maintenance->scheduled_at->timestamp,
                    VehicleMaintenance::class,
                    (int) $maintenance->getKey(),
                    'Mantenimiento próximo · '.$maintenance->vehicle->display_name,
                    sprintf(
                        'El mantenimiento %s está programado para %s.',
                        $maintenance->maintenance_type,
                        $maintenance->scheduled_at->format('d/m/Y h:i A'),
                    ),
                );
            });

        Vehicle::query()
            ->whereNotNull('next_maintenance_at')
            ->whereRaw('mileage >= next_maintenance_at - ?', [500])
            ->get()
            ->each(function (Vehicle $vehicle) use ($company, $dispatch): void {
                $this->queueInternalChannels(
                    $company,
                    $dispatch,
                    'maintenance-mileage:'.$vehicle->getKey().':'.$vehicle->next_maintenance_at,
                    Vehicle::class,
                    (int) $vehicle->getKey(),
                    'Mantenimiento por kilometraje · '.$vehicle->plate,
                    sprintf(
                        '%s tiene %s km y su próximo mantenimiento está definido en %s km.',
                        $vehicle->display_name,
                        number_format($vehicle->mileage),
                        number_format((int) $vehicle->next_maintenance_at),
                    ),
                );
            });

        Customer::query()
            ->where('status', 'active')
            ->whereNotNull('license_expiry')
            ->whereBetween('license_expiry', [$now->toDateString(), $now->addDays($documentDays)->toDateString()])
            ->get()
            ->each(function (Customer $customer) use ($company, $dispatch): void {
                $this->queueInternalChannels(
                    $company,
                    $dispatch,
                    'customer-license:'.$customer->getKey().':'.$customer->license_expiry->format('Ymd'),
                    Customer::class,
                    (int) $customer->getKey(),
                    'Licencia próxima a vencer · '.$customer->full_name,
                    'La licencia del cliente '.$customer->full_name.' vence el '.$customer->license_expiry->format('d/m/Y').'.',
                );
            });

        Vehicle::query()
            ->where(function ($query) use ($now, $documentDays): void {
                $from = $now->toDateString();
                $to = $now->addDays($documentDays)->toDateString();

                $query->whereBetween('insurance_expires_at', [$from, $to])
                    ->orWhereBetween('registration_expires_at', [$from, $to]);
            })
            ->get()
            ->each(function (Vehicle $vehicle) use ($company, $dispatch, $now, $documentDays): void {
                $from = $now->startOfDay();
                $to = $now->addDays($documentDays)->endOfDay();

                foreach ([
                    'insurance' => $vehicle->insurance_expires_at,
                    'registration' => $vehicle->registration_expires_at,
                ] as $document => $expiresAt) {
                    if ($expiresAt === null || ! $expiresAt->between($from, $to)) {
                        continue;
                    }

                    $label = $document === 'insurance' ? 'Seguro' : 'Matrícula/documento';

                    $this->queueInternalChannels(
                        $company,
                        $dispatch,
                        'vehicle-document:'.$document.':'.$vehicle->getKey().':'.$expiresAt->format('Ymd'),
                        Vehicle::class,
                        (int) $vehicle->getKey(),
                        $label.' próximo a vencer · '.$vehicle->plate,
                        $label.' de '.$vehicle->display_name.' vence el '.$expiresAt->format('d/m/Y').'.',
                    );
                }
            });
    }

    private function queueCustomerChannels(
        Company $company,
        AutomationDispatchService $dispatch,
        string $eventKey,
        string $subjectType,
        int $subjectId,
        ?string $email,
        ?string $phone,
        string $title,
        string $message,
    ): void {
        if ((bool) $company->setting('automation.email_enabled', true) && $email !== null) {
            $dispatch->queueOnce(
                $company,
                'email',
                $eventKey,
                $subjectType,
                $subjectId,
                $email,
                $title,
                $message,
            );
        }

        if ((bool) $company->setting('automation.whatsapp_enabled', false) && $phone !== null) {
            $dispatch->queueOnce(
                $company,
                'whatsapp',
                $eventKey,
                $subjectType,
                $subjectId,
                $phone,
                $title,
                $message,
            );
        }
    }

    private function queueInternalChannels(
        Company $company,
        AutomationDispatchService $dispatch,
        string $eventKey,
        string $subjectType,
        int $subjectId,
        string $title,
        string $message,
    ): void {
        if ((bool) $company->setting('automation.email_enabled', true) && $company->email !== null) {
            $dispatch->queueOnce(
                $company,
                'email',
                $eventKey,
                $subjectType,
                $subjectId,
                $company->email,
                $title,
                $message,
            );
        }

        if ((bool) $company->setting('automation.whatsapp_enabled', false) && $company->phone !== null) {
            $dispatch->queueOnce(
                $company,
                'whatsapp',
                $eventKey,
                $subjectType,
                $subjectId,
                $company->phone,
                $title,
                $message,
            );
        }
    }
}
