<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthlyIncome = collect(range(5, 0))
            ->reverse()
            ->map(function (int $offset): array {
                $date = now()->subMonths($offset);

                return [
                    'label' => ucfirst($date->translatedFormat('M')),
                    'value' => (float) Payment::query()
                        ->whereBetween('paid_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
                        ->sum('amount'),
                ];
            });

        $calendarReservations = Reservation::query()
            ->with(['customer', 'vehicle.model.brand', 'category'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start_at', [$monthStart, $monthEnd])
            ->orderBy('start_at')
            ->get()
            ->map(fn (Reservation $reservation): array => [
                'date' => $reservation->start_at->toDateString(),
                'day' => $reservation->start_at->day,
                'time' => $reservation->start_at->format('h:i A'),
                'customer' => $reservation->customer->full_name,
                'vehicle' => $reservation->vehicle?->display_name ?? $reservation->category->name,
                'url' => route('reservations.show', $reservation),
                'status' => $reservation->status,
            ]);

        return view('dashboard', [
            'metrics' => [
                'available_vehicles' => Vehicle::query()->where('status', 'available')->count(),
                'open_rentals' => Rental::query()->where('status', 'open')->count(),
                'today_reservations' => Reservation::query()
                    ->whereDate('start_at', today())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'month_collected' => Payment::query()
                    ->whereBetween('paid_at', [$monthStart, $monthEnd])
                    ->sum('amount'),
                'outstanding' => Invoice::query()->where('balance', '>', 0)->sum('balance'),
                'active_customers' => Customer::query()->where('status', 'active')->count(),
            ],
            'fleetStatus' => Vehicle::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'upcomingReservations' => Reservation::query()
                ->with(['customer', 'vehicle.model.brand', 'category'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('start_at', '>=', now()->startOfDay())
                ->orderBy('start_at')
                ->limit(6)
                ->get(),
            'activeRentals' => Rental::query()
                ->with(['customer', 'vehicle.model.brand'])
                ->where('status', 'open')
                ->orderBy('expected_return_at')
                ->limit(6)
                ->get(),
            'monthlyIncome' => $monthlyIncome,
            'calendar' => [
                'title' => ucfirst(now()->translatedFormat('F Y')),
                'firstWeekday' => Carbon::create($monthStart->year, $monthStart->month, 1)->dayOfWeekIso,
                'daysInMonth' => $monthStart->daysInMonth,
                'reservations' => $calendarReservations,
            ],
        ]);
    }
}
