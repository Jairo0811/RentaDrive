<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tenancy\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;

final class RentalRequest extends FormRequest
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
            'reservation_id' => ['nullable', TenantValidation::exists('reservations')],
            'customer_id' => ['required', TenantValidation::exists('customers')],
            'vehicle_id' => ['required', TenantValidation::exists('vehicles')],
            'start_at' => ['required', 'date'],
            'expected_return_at' => ['required', 'date', 'after:start_at'],
            'opening_mileage' => ['required', 'integer', 'min:0'],
            'fuel_out' => ['required', 'numeric', 'between:0,100'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
