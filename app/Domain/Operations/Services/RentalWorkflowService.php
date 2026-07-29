<?php

declare(strict_types=1);

namespace App\Domain\Operations\Services;

use App\Models\Invoice;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RentalWorkflowService
{
    public function __construct(
        private readonly ReservationAvailabilityService $availability,
        private readonly ReferenceNumberService $references,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function open(array $data, ?Reservation $reservation = null): Rental
    {
        return DB::transaction(function () use ($data, $reservation): Rental {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->lockForUpdate()->findOrFail($data['vehicle_id']);
            $startAt = Carbon::parse($data['start_at']);
            $endAt = Carbon::parse($data['expected_return_at']);

            if (! $this->availability->isVehicleAvailable(
                $vehicle,
                $startAt,
                $endAt,
                $reservation?->getKey(),
            )) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'El vehículo no está disponible en el período seleccionado.',
                ]);
            }

            $days = $this->availability->rentalDays($startAt, $endAt);
            $subtotal = round($days * (float) $data['daily_rate'], 2);
            $fees = round((float) ($data['fees'] ?? 0), 2);
            $taxRate = (float) SettingValue::get('billing.tax_rate', '18');
            $taxes = round(($subtotal + $fees) * ($taxRate / 100), 2);
            $total = $subtotal + $fees + $taxes;

            $rental = Rental::query()->create([
                ...$data,
                'code' => $this->references->generate(Rental::class, 'code', 'ALQ'),
                'reservation_id' => $reservation?->getKey(),
                'subtotal' => $subtotal,
                'taxes' => $taxes,
                'total' => $total,
                'status' => 'open',
                'opened_by' => auth()->id(),
            ]);

            $vehicle->update(['status' => 'rented']);
            $reservation?->update(['status' => 'converted']);

            Invoice::query()->create([
                'number' => $this->references->generate(Invoice::class, 'number', 'FAC'),
                'rental_id' => $rental->getKey(),
                'customer_id' => $rental->customer_id,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'subtotal' => $subtotal + $fees,
                'tax' => $taxes,
                'discount' => 0,
                'total' => $total,
                'paid_amount' => 0,
                'balance' => $total,
                'status' => 'pending',
            ]);

            return $rental->load(['customer', 'vehicle.model.brand', 'invoice']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function close(Rental $rental, array $data): Rental
    {
        if ($rental->status !== 'open') {
            throw ValidationException::withMessages([
                'status' => 'Solo se puede cerrar un alquiler abierto.',
            ]);
        }

        return DB::transaction(function () use ($rental, $data): Rental {
            $vehicleStatus = (string) ($data['vehicle_status'] ?? 'available');
            unset($data['vehicle_status']);

            $returnedAt = Carbon::parse($data['returned_at']);
            $days = $this->availability->rentalDays($rental->start_at, $returnedAt);
            $subtotal = round($days * (float) $rental->daily_rate, 2);
            $fees = round((float) ($data['fees'] ?? $rental->fees), 2);
            $taxRate = (float) SettingValue::get('billing.tax_rate', '18');
            $taxes = round(($subtotal + $fees) * ($taxRate / 100), 2);
            $total = $subtotal + $fees + $taxes;

            $rental->update([
                ...$data,
                'subtotal' => $subtotal,
                'fees' => $fees,
                'taxes' => $taxes,
                'total' => $total,
                'status' => 'closed',
                'closed_by' => auth()->id(),
            ]);

            $rental->vehicle()->update([
                'status' => $vehicleStatus,
                'mileage' => $data['closing_mileage'],
            ]);

            $invoice = $rental->invoice;
            if ($invoice !== null) {
                $balance = max(0, $total - (float) $invoice->paid_amount);
                $invoice->update([
                    'subtotal' => $subtotal + $fees,
                    'tax' => $taxes,
                    'total' => $total,
                    'balance' => $balance,
                    'status' => $balance <= 0 ? 'paid' : ((float) $invoice->paid_amount > 0 ? 'partial' : 'pending'),
                ]);
            }

            return $rental->fresh(['customer', 'vehicle.model.brand', 'invoice']);
        });
    }
}
