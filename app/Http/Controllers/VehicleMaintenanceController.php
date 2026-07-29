<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\VehicleMaintenanceRequest;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use Illuminate\Http\RedirectResponse;

final class VehicleMaintenanceController extends Controller
{
    public function store(VehicleMaintenanceRequest $request): RedirectResponse
    {
        $maintenance = VehicleMaintenance::query()->create($request->validated());
        $this->syncVehicleStatus($maintenance);

        return back()->with('status', 'Mantenimiento registrado.');
    }

    public function update(VehicleMaintenanceRequest $request, VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->update($request->validated());
        $this->syncVehicleStatus($maintenance);

        return back()->with('status', 'Mantenimiento actualizado.');
    }

    public function destroy(VehicleMaintenance $maintenance): RedirectResponse
    {
        $maintenance->delete();

        return back()->with('status', 'Mantenimiento eliminado.');
    }

    private function syncVehicleStatus(VehicleMaintenance $maintenance): void
    {
        /** @var Vehicle $vehicle */
        $vehicle = $maintenance->vehicle;

        if ($maintenance->status === 'in_progress') {
            $vehicle->update(['status' => 'maintenance']);
        }

        if ($maintenance->status === 'completed' && $vehicle->status === 'maintenance') {
            $vehicle->update([
                'status' => 'available',
                'mileage' => max($vehicle->mileage, (int) ($maintenance->mileage ?? 0)),
            ]);
        }
    }
}
