<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tenancy\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InspectionRequest extends FormRequest
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
        return [
            'rental_id' => ['required', TenantValidation::exists('rentals')],
            'type' => ['required', Rule::in(['delivery', 'return'])],
            'inspected_at' => ['required', 'date'],
            'mileage' => ['required', 'integer', 'min:0'],
            'fuel_level' => ['required', 'numeric', 'between:0,100'],
            'body_condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'interior_condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'tires_condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'replace'])],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'damages' => ['nullable', 'string', 'max:4000'],
            'photos' => ['nullable', 'array', 'max:12'],
            'photos.*' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
