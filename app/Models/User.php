<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Security\Enums\RoleName;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['company_id', 'branch_id', 'name', 'email', 'password', 'is_active', 'is_platform_admin'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_last_used_step'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'branch_id' => 'integer',
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_platform_admin' => 'boolean',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isPlatformAdmin(): bool
    {
        return $this->is_platform_admin === true;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Sucursal a la que queda limitada la visibilidad del usuario, o null si ve toda la empresa
     * (Administrador, Gerente, administrador de plataforma o usuario sin sucursal).
     */
    public function branchRestriction(): ?int
    {
        if ($this->branch_id === null || $this->isPlatformAdmin()) {
            return null;
        }

        if ($this->hasAnyRole([RoleName::ADMINISTRATOR->value, RoleName::MANAGER->value])) {
            return null;
        }

        return $this->hasAnyRole([RoleName::RENTAL_AGENT->value, RoleName::INSPECTOR->value]) ? (int) $this->branch_id : null;
    }

    /** Cuentas que deben proteger su acceso con MFA: SuperAdmin de plataforma y administradores de empresa. */
    public function isPrivileged(): bool
    {
        return $this->isPlatformAdmin() || $this->hasRole(RoleName::ADMINISTRATOR->value);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
