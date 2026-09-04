<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tenancy\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        return [
            'vehicle_model_id' => ['required', TenantValidation::exists('vehicle_models')],
            'vehicle_category_id' => ['required', TenantValidation::exists('vehicle_categories')],
            'code' => ['required', 'string', 'max:30', TenantValidation::unique('vehicles', 'code')->ignore($vehicle)],
            'plate' => ['required', 'string', 'max:20', TenantValidation::unique('vehicles', 'plate')->ignore($vehicle)],
            'vin' => ['nullable', 'string', 'max:50', TenantValidation::unique('vehicles', 'vin')->ignore($vehicle)],
            'color' => ['required', 'string', 'max:40'],
            'transmission' => ['required', Rule::in(['automatic', 'manual'])],
            'fuel_type' => ['required', Rule::in(['gasoline', 'diesel', 'hybrid', 'electric'])],
            'seats' => ['required', 'integer', 'between:1,60'],
            'mileage' => ['required', 'integer', 'min:0'],
            'daily_rate_override' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['available', 'reserved', 'rented', 'maintenance', 'inactive'])],
            'acquisition_date' => ['nullable', 'date'],
            'next_maintenance_at' => ['nullable', 'integer', 'gte:mileage'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
