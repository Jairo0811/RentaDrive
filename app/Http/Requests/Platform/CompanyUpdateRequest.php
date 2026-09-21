<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class CompanyUpdateRequest extends FormRequest
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->route('company');
        $companyId = $company instanceof Company ? $company->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:160'],
            'rnc' => ['nullable', 'string', 'max:20', Rule::unique('companies', 'rnc')->ignore($companyId)],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('companies', 'slug')->ignore($companyId)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'currency' => ['required', 'string', 'size:3'],
            'timezone' => ['required', 'string', 'max:80'],
            'plan_code' => ['required', Rule::in(array_keys((array) config('rentadrive.plans')))],
            'status' => ['required', Rule::in(['trial', 'active', 'suspended', 'cancelled'])],
            'trial_ends_at' => ['nullable', 'date', 'required_if:status,trial'],
        ];
    }
}
