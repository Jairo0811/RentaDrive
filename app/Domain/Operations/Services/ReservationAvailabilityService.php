<?php

declare(strict_types=1);

namespace App\Domain\Operations\Services;

use App\Models\Branch;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
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

    public function availableCategoryCount(
        Branch $branch,
        VehicleCategory $category,
        CarbonInterface $startAt,
        CarbonInterface $endAt,
    ): int {
        $vehicleIds = Vehicle::query()
            ->where('branch_id', $branch->getKey())
            ->where('vehicle_category_id', $category->getKey())
            ->whereNotIn('status', ['maintenance', 'inactive'])
            ->pluck('id');

        if ($vehicleIds->isEmpty()) {
            return 0;
        }

        $blockedByReservations = Reservation::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->pluck('vehicle_id')
            ->filter()
            ->unique();

        $blockedByRentals = Rental::query()
            ->whereIn('vehicle_id', $vehicleIds)
            ->where('status', 'open')
            ->where('start_at', '<', $endAt)
            ->where('expected_return_at', '>', $startAt)
            ->pluck('vehicle_id')
            ->filter()
            ->unique();

        $blockedPhysicalVehicles = $blockedByReservations
            ->merge($blockedByRentals)
            ->unique()
            ->count();

        $physicalAvailability = max(0, $vehicleIds->count() - $blockedPhysicalVehicles);

        $unassignedCategoryReservations = Reservation::query()
            ->where('branch_id', $branch->getKey())
            ->where('vehicle_category_id', $category->getKey())
            ->whereNull('vehicle_id')
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->count();

        return max(0, $physicalAvailability - $unassignedCategoryReservations);
    }

    public function rentalDays(CarbonInterface $startAt, CarbonInterface $endAt): int
    {
        return max(1, (int) ceil($startAt->diffInMinutes($endAt) / 1440));
    }
}
