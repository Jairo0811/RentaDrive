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
            'mode' => ['nullable', Rule::in(['standard', 'mobile'])],
            'inspected_at' => ['required', 'date'],
            'mileage' => ['required', 'integer', 'min:0'],
            'fuel_level' => ['required', 'numeric', 'between:0,100'],
            'body_condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'interior_condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'damaged'])],
            'tires_condition' => ['required', Rule::in(['excellent', 'good', 'fair', 'replace'])],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'accessories_checklist' => ['nullable', 'array', 'max:20'],
            'accessories_checklist.*' => ['string', Rule::in([
                'spare_tire',
                'jack',
                'warning_triangle',
                'documents',
                'first_aid',
                'tools',
                'floor_mats',
                'charger',
            ])],
            'damages' => ['nullable', 'string', 'max:4000'],
            'damage_area' => ['nullable', 'array', 'max:12'],
            'damage_area.*' => ['nullable', 'string', 'max:80'],
            'damage_severity' => ['nullable', 'array', 'max:12'],
            'damage_severity.*' => ['nullable', Rule::in(['minor', 'moderate', 'major'])],
            'damage_description' => ['nullable', 'array', 'max:12'],
            'damage_description.*' => ['nullable', 'string', 'max:500'],
            'photos' => [
                Rule::requiredIf(fn (): bool => $this->input('mode') === 'mobile'),
                'array',
                'max:12',
            ],
            'photos.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
