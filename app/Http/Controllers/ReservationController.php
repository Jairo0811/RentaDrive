<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Operations\Services\ReferenceNumberService;
use App\Domain\Operations\Services\ReservationAvailabilityService;
use App\Http\Requests\ReservationRequest;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class ReservationController extends Controller
{
    public function index(Request $request): View
    {
        $reservations = Reservation::query()
            ->with(['customer', 'category', 'vehicle.model.brand'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', $search)
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('start_at')
            ->paginate(15)
            ->withQueryString();

        return view('reservations.index', compact('reservations'));
    }

    public function create(): View
    {
        return $this->formView(new Reservation());
    }

    public function store(
        ReservationRequest $request,
        ReservationAvailabilityService $availability,
        ReferenceNumberService $references,
    ): RedirectResponse {
        $data = $this->prepareData($request, $availability);
        $reservation = Reservation::query()->create([
            ...$data,
            'code' => $references->generate(Reservation::class, 'code', 'RES'),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('reservations.show', $reservation)->with('status', 'Reserva creada.');
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load(['customer', 'category', 'vehicle.model.brand', 'creator', 'rental']);

        return view('reservations.show', compact('reservation'));
    }

    public function edit(Reservation $reservation): View
    {
        if ($reservation->status === 'converted') {
            abort(409, 'Una reserva convertida en alquiler no puede editarse.');
        }

        return $this->formView($reservation);
    }

    public function update(
        ReservationRequest $request,
        Reservation $reservation,
        ReservationAvailabilityService $availability,
    ): RedirectResponse {
        $reservation->update($this->prepareData($request, $availability, $reservation));

        return redirect()->route('reservations.show', $reservation)->with('status', 'Reserva actualizada.');
    }

    public function destroy(Reservation $reservation): RedirectResponse
    {
        if ($reservation->status === 'converted') {
            throw ValidationException::withMessages([
                'reservation' => 'No puedes cancelar una reserva que ya fue convertida en alquiler.',
            ]);
        }

        $reservation->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return redirect()->route('reservations.index')->with('status', 'Reserva cancelada.');
    }

    private function formView(Reservation $reservation): View
    {
        return view('reservations.form', [
            'reservation' => $reservation,
            'customers' => Customer::query()->where('status', 'active')->orderBy('first_name')->get(),
            'categories' => VehicleCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->with(['model.brand', 'category'])->whereNotIn('status', ['maintenance', 'inactive'])->get(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareData(
        ReservationRequest $request,
        ReservationAvailabilityService $availability,
        ?Reservation $reservation = null,
    ): array {
        $data = $request->validated();
        $startAt = Carbon::parse($data['start_at']);
        $endAt = Carbon::parse($data['end_at']);

        if (! empty($data['vehicle_id'])) {
            /** @var Vehicle $vehicle */
            $vehicle = Vehicle::query()->findOrFail($data['vehicle_id']);

            if ((int) $vehicle->vehicle_category_id !== (int) $data['vehicle_category_id']) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'El vehículo no pertenece a la categoría seleccionada.',
                ]);
            }

            if (! $availability->isVehicleAvailable($vehicle, $startAt, $endAt, $reservation?->getKey())) {
                throw ValidationException::withMessages([
                    'vehicle_id' => 'El vehículo no está disponible en ese período.',
                ]);
            }
        }

        $data['estimated_total'] = round(
            $availability->rentalDays($startAt, $endAt) * (float) $data['daily_rate'],
            2,
        );
        $data['cancelled_at'] = $data['status'] === 'cancelled' ? now() : null;

        return $data;
    }
}
