<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Commercial\PlanLimits;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class VehicleController extends Controller
{
    public function __construct(private readonly PlanLimits $planLimits) {}

    public function index(Request $request): View
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $vehicles = Vehicle::query()
            ->with(['branch', 'model.brand', 'category'])
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
            ->when($request->filled('branch'), fn ($query) => $query->where('branch_id', $request->integer('branch')))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'categories' => VehicleCategory::query()->orderBy('name')->get(),
            'branches' => $company->branches()->where('is_active', true)->orderByDesc('is_primary')->orderBy('name')->get(),
            'currency' => $company->currency,
        ]);
    }

    public function create(): View
    {
        return $this->formView(new Vehicle);
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $this->planLimits->ensureCanAdd($company, 'vehicles', Vehicle::query()->count());

        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('vehicles/'.$company->getKey(), 'public');
        }

        $vehicle = Vehicle::query()->create($data);

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Vehículo registrado.');
    }

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load([
            'branch',
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
        $company = $request->user()?->company;
        abort_unless($company !== null, 403);

        $data = $request->safe()->except(['photo', 'remove_photo']);

        if ($request->boolean('remove_photo') && $vehicle->photo_path !== null) {
            Storage::disk('public')->delete($vehicle->photo_path);
            $data['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($vehicle->photo_path !== null) {
                Storage::disk('public')->delete($vehicle->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('vehicles/'.$company->getKey(), 'public');
        }

        $vehicle->update($data);

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Vehículo actualizado.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->rentals()->exists() || $vehicle->reservations()->exists()) {
            throw ValidationException::withMessages([
                'vehicle' => 'El vehículo tiene operaciones relacionadas. Márcalo como inactivo.',
            ]);
        }

        if ($vehicle->photo_path !== null) {
            Storage::disk('public')->delete($vehicle->photo_path);
        }

        $vehicle->delete();

        return redirect()->route('vehicles.index')->with('status', 'Vehículo eliminado.');
    }

    private function formView(Vehicle $vehicle): View
    {
        $company = auth()->user()?->company;
        abort_unless($company !== null, 403);

        return view('vehicles.form', [
            'vehicle' => $vehicle,
            'models' => VehicleModel::query()->with('brand')->where('is_active', true)->orderByDesc('year')->get(),
            'categories' => VehicleCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'branches' => $company->branches()->where('is_active', true)->orderByDesc('is_primary')->orderBy('name')->get(),
        ]);
    }
}
