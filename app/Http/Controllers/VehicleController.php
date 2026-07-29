<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::query()
            ->with(['model.brand', 'category'])
            ->withCount(['rentals', 'maintenances'])
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('q')->value().'%';
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', $search)
                        ->orWhere('plate', 'like', $search)
                        ->orWhere('vin', 'like', $search)
                        ->orWhereHas('model', fn ($query) => $query->where('name', 'like', $search))
                        ->orWhereHas('model.brand', fn ($query) => $query->where('name', 'like', $search));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('vehicle_category_id', $request->integer('category')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'categories' => VehicleCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new Vehicle());
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::query()->create($request->validated());

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Vehículo registrado.');
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load([
            'model.brand',
            'category',
            'maintenances' => fn ($query) => $query->latest('scheduled_at'),
            'rentals' => fn ($query) => $query->with('customer')->latest()->limit(10),
        ]);

        return view('vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle): View
    {
        return $this->formView($vehicle);
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Vehículo actualizado.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->rentals()->exists() || $vehicle->reservations()->exists()) {
            throw ValidationException::withMessages([
                'vehicle' => 'El vehículo tiene operaciones relacionadas. Márcalo como inactivo.',
            ]);
        }

        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('status', 'Vehículo eliminado.');
    }

    private function formView(Vehicle $vehicle): View
    {
        return view('vehicles.form', [
            'vehicle' => $vehicle,
            'models' => VehicleModel::query()->with('brand')->where('is_active', true)->orderByDesc('year')->get(),
            'categories' => VehicleCategory::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
