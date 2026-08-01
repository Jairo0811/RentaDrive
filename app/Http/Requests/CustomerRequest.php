<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\DominicanCedula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CustomerRequest extends FormRequest
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
        $customer = $this->route('customer');
        $documentType = (string) $this->input('document_type');

        $documentRules = [
            'required',
            'string',
            Rule::unique('customers')->ignore($customer),
        ];

        match ($documentType) {
            'cedula' => array_push($documentRules, 'digits:11', new DominicanCedula),
            'rnc' => array_push($documentRules, 'digits:9'),
            'passport' => array_push($documentRules, 'min:6', 'max:20', 'regex:/^[A-Z0-9]+$/i'),
            default => array_push($documentRules, 'min:3', 'max:30'),
        };

        return [
            'document_type' => ['required', Rule::in(['cedula', 'passport', 'rnc', 'other'])],
            'document_number' => $documentRules,
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'license_number' => ['nullable', 'string', 'max:50', Rule::unique('customers')->ignore($customer)],
            'license_expiry' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.digits' => 'El número debe contener exactamente :digits dígitos.',
            'document_number.min' => 'El número debe contener al menos :min caracteres.',
            'document_number.max' => 'El número no puede superar :max caracteres.',
            'document_number.regex' => 'El pasaporte solo puede contener letras y números.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $documentType = (string) $this->input('document_type');
        $documentNumber = trim((string) $this->input('document_number'));

        if (in_array($documentType, ['cedula', 'rnc'], true)) {
            $documentNumber = preg_replace('/\D+/', '', $documentNumber) ?? '';
        } elseif ($documentType === 'passport') {
            $documentNumber = strtoupper(preg_replace('/\s+/', '', $documentNumber) ?? '');
        }

        $this->merge([
            'document_number' => $documentNumber,
        ]);
    }
}
