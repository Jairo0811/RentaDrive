<?php

declare(strict_types=1);

namespace App\Support\Security;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Illuminate\Validation\ValidationException;

final class AdministrativeContinuity
{
    public function ensureCanDelete(User $user, string $field = 'user'): void
    {
        if ($user->isPlatformAdmin()) {
            if ($user->is_active && $this->activePlatformAdministrators() <= 1) {
                throw ValidationException::withMessages([
                    $field => 'No puedes eliminar el último SuperAdmin activo de la plataforma.',
                ]);
            }

            return;
        }

        if (
            $user->company_id !== null
            && $user->is_active
            && $user->hasRole(RoleName::ADMINISTRATOR->value)
            && $this->activeTenantAdministrators((int) $user->company_id) <= 1
        ) {
            throw ValidationException::withMessages([
                $field => 'No puedes eliminar el último Administrador activo de la empresa.',
            ]);
        }
    }

    public function ensureCanChangeTenantAccess(User $user, string $newRole, bool $newActive): void
    {
        if (
            $user->isPlatformAdmin()
            || $user->company_id === null
            || ! $user->is_active
            || ! $user->hasRole(RoleName::ADMINISTRATOR->value)
        ) {
            return;
        }

        $removesAdministrativeAccess = ! $newActive
            || $newRole !== RoleName::ADMINISTRATOR->value;

        if (
            $removesAdministrativeAccess
            && $this->activeTenantAdministrators((int) $user->company_id) <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => 'La empresa debe conservar al menos un Administrador activo.',
            ]);
        }
    }

    private function activePlatformAdministrators(): int
    {
        return User::query()
            ->where('is_platform_admin', true)
            ->where('is_active', true)
            ->count();
    }

    private function activeTenantAdministrators(int $companyId): int
    {
        return User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', RoleName::ADMINISTRATOR->value))
            ->count();
    }
}
