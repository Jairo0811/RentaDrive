<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VehicleBrand;
use App\Models\VehicleCategory;
use App\Models\VehicleModel;
use App\Support\Tenancy\TenantValidation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class FleetCatalogController extends Controller
{
    public function index(): View
    {
        return view('fleet.catalogs', [
            'brands' => VehicleBrand::query()->withCount('models')->orderBy('name')->get(),
            'categories' => VehicleCategory::query()->withCount('vehicles')->orderBy('name')->get(),
            'models' => VehicleModel::query()->with('brand')->withCount('vehicles')->orderByDesc('year')->orderBy('name')->get(),
        ]);
    }

    public function storeBrand(Request $request): RedirectResponse
    {
        VehicleBrand::query()->create($request->validate([
            'name' => ['required', 'string', 'max:80', TenantValidation::unique('vehicle_brands', 'name')],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)]);

        return back()->with('status', 'Marca creada.');
    }

    public function updateBrand(Request $request, VehicleBrand $brand): RedirectResponse
    {
        $brand->update($request->validate([
            'name' => ['required', 'string', 'max:80', TenantValidation::unique('vehicle_brands', 'name')->ignore($brand)],
            'is_active' => ['required', 'boolean'],
        ]));

        return back()->with('status', 'Marca actualizada.');
    }

    public function destroyBrand(VehicleBrand $brand): RedirectResponse
    {
        if ($brand->models()->exists()) {
            throw ValidationException::withMessages(['brand' => 'No puedes eliminar una marca con modelos asociados.']);
        }

        $brand->delete();

        return back()->with('status', 'Marca eliminada.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        VehicleCategory::query()->create($request->validate([
            'code' => ['required', 'string', 'max:20', TenantValidation::unique('vehicle_categories', 'code')],
            'name' => ['required', 'string', 'max:80', TenantValidation::unique('vehicle_categories', 'name')],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]) + ['is_active' => true]);

        return back()->with('status', 'Categoría creada.');
    }

    public function updateCategory(Request $request, VehicleCategory $category): RedirectResponse
    {
        $category->update($request->validate([
            'code' => ['required', 'string', 'max:20', TenantValidation::unique('vehicle_categories', 'code')->ignore($category)],
            'name' => ['required', 'string', 'max:80', TenantValidation::unique('vehicle_categories', 'name')->ignore($category)],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]));

        return back()->with('status', 'Categoría actualizada.');
    }

    public function destroyCategory(VehicleCategory $category): RedirectResponse
    {
        if ($category->vehicles()->exists()) {
            throw ValidationException::withMessages(['category' => 'No puedes eliminar una categoría con vehículos asociados.']);
        }

        $category->delete();

        return back()->with('status', 'Categoría eliminada.');
    }

    public function storeModel(Request $request): RedirectResponse
    {
        $brandId = (int) $request->input('vehicle_brand_id');
        $year = (int) $request->input('year');

        VehicleModel::query()->create($request->validate([
            'vehicle_brand_id' => ['required', TenantValidation::exists('vehicle_brands')],
            'name' => [
                'required',
                'string',
                'max:80',
                TenantValidation::unique('vehicle_models', 'name')
                    ->where('vehicle_brand_id', $brandId)
                    ->where('year', $year),
            ],
            'year' => ['required', 'integer', 'between:1950,'.(now()->year + 2)],
        ]) + ['is_active' => true]);

        return back()->with('status', 'Modelo creado.');
    }

    public function updateModel(Request $request, VehicleModel $model): RedirectResponse
    {
        $brandId = (int) $request->input('vehicle_brand_id');
        $year = (int) $request->input('year');

        $model->update($request->validate([
            'vehicle_brand_id' => ['required', TenantValidation::exists('vehicle_brands')],
            'name' => [
                'required',
                'string',
                'max:80',
                TenantValidation::unique('vehicle_models', 'name')
                    ->where('vehicle_brand_id', $brandId)
                    ->where('year', $year)
                    ->ignore($model),
            ],
            'year' => ['required', 'integer', 'between:1950,'.(now()->year + 2)],
            'is_active' => ['required', 'boolean'],
        ]));

        return back()->with('status', 'Modelo actualizado.');
    }

    public function destroyModel(VehicleModel $model): RedirectResponse
    {
        if ($model->vehicles()->exists()) {
            throw ValidationException::withMessages(['model' => 'No puedes eliminar un modelo con vehículos asociados.']);
        }

        $model->delete();

        return back()->with('status', 'Modelo eliminado.');
    }
}
