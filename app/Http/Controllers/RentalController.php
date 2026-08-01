<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Operations\Services\RentalWorkflowService;
use App\Http\Requests\RentalRequest;
use App\Models\Customer;
use App\Models\Rental;
use App\Models\Reservation;
use App\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RentalController extends Controller
{
    public function index(Request $request): View
    {
        $rentals = Rental::query()
            ->with(['customer', 'vehicle.model.brand', 'invoice'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', $search)
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search))
                        ->orWhereHas('vehicle', fn ($query) => $query->where('plate', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('start_at')
            ->paginate(15)
            ->withQueryString();

        return view('rentals.index', compact('rentals'));
    }

    public function create(Request $request): View
    {
        $reservation = $request->filled('reservation')
            ? Reservation::query()->with(['customer', 'vehicle', 'category'])->findOrFail($request->integer('reservation'))
            : null;

        return view('rentals.form', [
            'rental' => new Rental,
            'reservation' => $reservation,
            'customers' => Customer::query()->where('status', 'active')->orderBy('first_name')->get(),
            'vehicles' => Vehicle::query()->with(['model.brand', 'category'])->whereNotIn('status', ['rented', 'maintenance', 'inactive'])->get(),
        ]);
    }

    public function store(RentalRequest $request, RentalWorkflowService $workflow): RedirectResponse
    {
        $reservation = $request->filled('reservation_id')
            ? Reservation::query()->findOrFail($request->integer('reservation_id'))
            : null;

        $rental = $workflow->open($request->validated(), $reservation);

        return redirect()->route('rentals.show', $rental)->with('status', 'Alquiler abierto y factura generada.');
    }

    public function show(Rental $rental): View
    {
        $rental->load([
            'customer',
            'vehicle.model.brand',
            'vehicle.category',
            'reservation',
            'inspections.inspector',
            'invoice.payments',
            'opener',
            'closer',
        ]);

        return view('rentals.show', compact('rental'));
    }

    public function close(Request $request, Rental $rental, RentalWorkflowService $workflow): RedirectResponse
    {
        $validated = $request->validate([
            'returned_at' => ['required', 'date', 'after_or_equal:'.$rental->start_at->format('Y-m-d H:i:s')],
            'closing_mileage' => ['required', 'integer', 'gte:'.$rental->opening_mileage],
            'fuel_in' => ['required', 'numeric', 'between:0,100'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'vehicle_status' => ['required', 'in:available,maintenance'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $workflow->close($rental, $validated);

        return redirect()->route('rentals.show', $rental)->with('status', 'Alquiler cerrado y factura recalculada.');
    }

    public function contract(Rental $rental): View
    {
        $rental->load(['customer', 'vehicle.model.brand', 'vehicle.category', 'invoice']);

        return view('documents.contract', compact('rental'));
    }
}
