<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class BranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
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
        $company = $this->route('company');
        $branch = $this->route('branch');
        $companyId = $company instanceof Company ? $company->getKey() : null;
        $branchId = $branch instanceof Branch ? $branch->getKey() : null;

        return [
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('branches', 'code')
                    ->where(fn ($query) => $query->where('company_id', $companyId))
                    ->ignore($branchId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
