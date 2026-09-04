<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Tenancy\TenantValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PaymentRequest extends FormRequest
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
            'invoice_id' => ['required', TenantValidation::exists('invoices')],
            'paid_at' => ['required', 'date'],
            'method' => ['required', Rule::in(['cash', 'card', 'transfer', 'other'])],
            'reference' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
