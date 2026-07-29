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

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'metrics' => [
                'available_vehicles' => Vehicle::query()->where('status', 'available')->count(),
                'open_rentals' => Rental::query()->where('status', 'open')->count(),
                'today_reservations' => Reservation::query()
                    ->whereDate('start_at', today())
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->count(),
                'month_collected' => Payment::query()
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
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
        ]);
    }
}
