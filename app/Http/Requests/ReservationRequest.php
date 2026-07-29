<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReservationRequest extends FormRequest
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
            'customer_id' => ['required', 'exists:customers,id'],
            'vehicle_category_id' => ['required', 'exists:vehicle_categories,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'pickup_location' => ['required', 'string', 'max:255'],
            'return_location' => ['required', 'string', 'max:255'],
            'daily_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'confirmed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
