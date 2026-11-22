<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        // Defensa en profundidad: la validación no debe ejecutarse (ni revelar errores) sobre un usuario de otra empresa.
        abort_if(
            $target instanceof User && $this->user()?->company_id !== $target->company_id,
            404,
        );

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $companyId = $this->user()?->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('is_active', true)),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
