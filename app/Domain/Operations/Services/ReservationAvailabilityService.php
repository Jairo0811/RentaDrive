<?php

declare(strict_types=1);

namespace App\Domain\Operations\Services;

use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use Carbon\CarbonInterface;

final class ReservationAvailabilityService
{
    public function isVehicleAvailable(
        Vehicle $vehicle,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
        ?int $ignoreReservationId = null,
    ): bool {
        if (in_array($vehicle->status, ['maintenance', 'inactive'], true)) {
            return false;
        }

        $reservationConflict = Reservation::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->whereIn('status', ['pending', 'confirmed'])
            ->when($ignoreReservationId, fn ($query) => $query->whereKeyNot($ignoreReservationId))
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        if ($reservationConflict) {
            return false;
        }

        return ! Rental::query()
            ->where('vehicle_id', $vehicle->getKey())
            ->where('status', 'open')
            ->where('start_at', '<', $endAt)
            ->where('expected_return_at', '>', $startAt)
            ->exists();
    }

    public function rentalDays(CarbonInterface $startAt, CarbonInterface $endAt): int
    {
        return max(1, (int) ceil($startAt->diffInMinutes($endAt) / 1440));
    }
}
