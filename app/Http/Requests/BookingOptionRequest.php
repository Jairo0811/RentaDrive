<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\BookingOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class BookingOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage configuration') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => Str::upper((string) $this->input('code')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $option = $this->route('booking_option');
        $companyId = $this->user()?->company_id;
        $optionId = $option instanceof BookingOption ? $option->getKey() : null;

        return [
            'code' => [
                'required',
                'string',
                'max:40',
                'alpha_dash',
                Rule::unique('booking_options', 'code')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($optionId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['extra', 'insurance'])],
            'pricing_mode' => ['required', Rule::in(['flat', 'per_day'])],
            'price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
