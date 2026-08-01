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
            'max:30',
            Rule::unique('customers')->ignore($customer),
        ];

        if ($documentType === 'cedula') {
            $documentRules[] = new DominicanCedula;
        }

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

    protected function prepareForValidation(): void
    {
        if ((string) $this->input('document_type') !== 'cedula') {
            return;
        }

        $this->merge([
            'document_number' => preg_replace('/\D+/', '', (string) $this->input('document_number')),
        ]);
    }
}
