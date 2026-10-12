<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Company;
use App\Rules\DominicanCedula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PublicBookingRequest extends FormRequest
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
        $company = $this->route('company');
        $companyId = $company instanceof Company ? $company->getKey() : null;
        $documentType = (string) $this->input('document_type');

        $documentRules = ['required', 'string'];

        match ($documentType) {
            'cedula' => array_push($documentRules, 'digits:11', new DominicanCedula),
            'passport' => array_push($documentRules, 'min:6', 'max:20', 'regex:/^[A-Z0-9]+$/i'),
            default => array_push($documentRules, 'max:30'),
        };

        return [
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true)),
            ],
            'vehicle_category_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_categories', 'id')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true)),
            ],
            'start_at' => ['required', 'date', 'after_or_equal:now'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'booking_option_ids' => ['nullable', 'array'],
            'booking_option_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('booking_options', 'id')->where(fn ($query) => $query
                    ->where('company_id', $companyId)
                    ->where('is_active', true)),
            ],
            'document_type' => ['required', Rule::in(['cedula', 'passport'])],
            'document_number' => $documentRules,
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.digits' => 'La cédula debe contener exactamente 11 dígitos.',
            'document_number.min' => 'El pasaporte debe contener al menos :min caracteres.',
            'document_number.max' => 'El documento no puede superar :max caracteres.',
            'document_number.regex' => 'El pasaporte solo puede contener letras y números.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $documentType = (string) $this->input('document_type');
        $documentNumber = trim((string) $this->input('document_number'));

        if ($documentType === 'cedula') {
            $documentNumber = preg_replace('/\D+/', '', $documentNumber) ?? '';
        } elseif ($documentType === 'passport') {
            $documentNumber = strtoupper(preg_replace('/\s+/', '', $documentNumber) ?? '');
        }

        $this->merge([
            'document_number' => $documentNumber,
        ]);
    }
}
