<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Visibilidad por sucursal (solo lectura/acceso): los roles operativos (Agente, Inspector) ven y abren únicamente los
 * registros de su sucursal. Administrador y Gerente ven toda la empresa. No es un TenantScope global: los cálculos de
 * disponibilidad y los servicios siguen viendo toda la empresa para no subestimar reservas de otras sucursales.
 *
 * @mixin Model
 */
trait BranchVisible
{
    /** @param Builder<static> $query */
    public function scopeVisibleToBranch(Builder $query, ?User $user = null): void
    {
        $branchId = ($user ?? auth()->user())?->branchRestriction();

        if ($branchId !== null) {
            $query->where($query->getModel()->getTable().'.branch_id', $branchId);
        }
    }

    /** Los enlaces de ruta (show/edit/update/destroy…) devuelven 404 para registros de otra sucursal. */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->newQuery()->visibleToBranch()->where($field ?? $this->getRouteKeyName(), $value)->first();
    }
}
