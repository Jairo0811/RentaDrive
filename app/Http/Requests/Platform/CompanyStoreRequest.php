<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class CompanyStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) ($this->input('slug') ?: $this->input('name'))),
            'currency' => Str::upper((string) $this->input('currency', 'DOP')),
            'branch_code' => Str::upper((string) $this->input('branch_code', 'PRINCIPAL')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'rnc' => ['nullable', 'string', 'max:20', Rule::unique('companies', 'rnc')],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('companies', 'slug')],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:80'],
            'branch_name' => ['required', 'string', 'max:120'],
            'branch_code' => ['required', 'string', 'max:30', 'alpha_dash'],
            'branch_address' => ['nullable', 'string', 'max:255'],
            'branch_city' => ['nullable', 'string', 'max:80'],
            'branch_phone' => ['nullable', 'string', 'max:30'],
            'branch_email' => ['nullable', 'email', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
