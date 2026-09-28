<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class FleetScheduleController extends Controller
{
    public function __invoke(Request $request): View
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $validated = $request->validate([
            'start' => ['nullable', 'date'],
            'branch' => ['nullable', 'integer'],
        ]);

        $start = isset($validated['start'])
            ? CarbonImmutable::parse((string) $validated['start'], $company->timezone)->startOfDay()
            : CarbonImmutable::now($company->timezone)->startOfDay();

        $days = collect(range(0, 13))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));

        $end = $start->addDays(14);

        $branches = $company->branches()
            ->where('is_active', true)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        $branchId = isset($validated['branch']) ? (int) $validated['branch'] : null;

        if ($branchId !== null && ! $branches->contains('id', $branchId)) {
            abort(404);
        }

        $vehicles = Vehicle::query()
            ->with([
                'branch',
                'model.brand',
                'category',
                'reservations' => fn ($query) => $query
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('start_at', '<', $end)
                    ->where('end_at', '>', $start)
                    ->orderBy('start_at'),
                'rentals' => fn ($query) => $query
                    ->where('status', 'open')
                    ->where('start_at', '<', $end)
                    ->where('expected_return_at', '>', $start)
                    ->orderBy('start_at'),
                'maintenances' => fn ($query) => $query
                    ->whereIn('status', ['scheduled', 'in_progress'])
                    ->whereBetween('scheduled_at', [$start, $end])
                    ->orderBy('scheduled_at'),
            ])
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('code')
            ->get();

        $rows = $vehicles->map(function (Vehicle $vehicle) use ($days): array {
            $cells = $days->map(function (CarbonImmutable $day) use ($vehicle): array {
                $dayStart = $day->startOfDay();
                $dayEnd = $day->addDay()->startOfDay();

                /** @var Rental|null $rental */
                $rental = $vehicle->rentals->first(
                    fn (Rental $item): bool => $item->start_at->lt($dayEnd)
                        && $item->expected_return_at->gt($dayStart),
                );

                if ($rental !== null) {
                    return [
                        'status' => 'rented',
                        'label' => $rental->code,
                        'url' => route('rentals.show', $rental),
                    ];
                }

                /** @var Reservation|null $reservation */
                $reservation = $vehicle->reservations->first(
                    fn (Reservation $item): bool => $item->start_at->lt($dayEnd)
                        && $item->end_at->gt($dayStart),
                );

                if ($reservation !== null) {
                    return [
                        'status' => 'reserved',
                        'label' => $reservation->code,
                        'url' => route('reservations.show', $reservation),
                    ];
                }

                $maintenance = $vehicle->maintenances->first(
                    fn ($item): bool => $item->scheduled_at->isSameDay($day),
                );

                if ($maintenance !== null || $vehicle->status === 'maintenance') {
                    return [
                        'status' => 'maintenance',
                        'label' => $maintenance?->maintenance_type ?? 'Mantenimiento',
                        'url' => route('vehicles.show', $vehicle),
                    ];
                }

                if ($vehicle->status === 'inactive') {
                    return [
                        'status' => 'inactive',
                        'label' => 'Inactivo',
                        'url' => route('vehicles.show', $vehicle),
                    ];
                }

                return [
                    'status' => 'available',
                    'label' => 'Disponible',
                    'url' => route('vehicles.show', $vehicle),
                ];
            });

            return [
                'vehicle' => $vehicle,
                'cells' => $cells,
            ];
        });

        return view('fleet.schedule', [
            'start' => $start,
            'days' => $days,
            'rows' => $rows,
            'branches' => $branches,
            'branchId' => $branchId,
        ]);
    }
}
