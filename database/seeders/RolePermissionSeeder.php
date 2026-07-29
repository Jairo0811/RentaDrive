<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Security\Enums\PermissionName;
use App\Domain\Security\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->syncRole(RoleName::ADMINISTRATOR, PermissionName::cases());
        $this->syncRole(RoleName::MANAGER, [
            PermissionName::VIEW_DASHBOARD,
            PermissionName::VIEW_REPORTS,
            PermissionName::VIEW_VEHICLES,
            PermissionName::VIEW_CUSTOMERS,
            PermissionName::VIEW_RESERVATIONS,
            PermissionName::VIEW_RENTALS,
            PermissionName::VIEW_INVOICES,
            PermissionName::VIEW_PAYMENTS,
        ]);
        $this->syncRole(RoleName::RENTAL_AGENT, [
            PermissionName::VIEW_DASHBOARD,
            PermissionName::VIEW_VEHICLES,
            PermissionName::VIEW_CUSTOMERS,
            PermissionName::MANAGE_CUSTOMERS,
            PermissionName::VIEW_RESERVATIONS,
            PermissionName::MANAGE_RESERVATIONS,
            PermissionName::VIEW_RENTALS,
            PermissionName::MANAGE_RENTALS,
            PermissionName::MANAGE_CONTRACTS,
            PermissionName::MANAGE_DELIVERIES,
            PermissionName::MANAGE_RETURNS,
            PermissionName::VIEW_INVOICES,
            PermissionName::VIEW_PAYMENTS,
        ]);
        $this->syncRole(RoleName::INSPECTOR, [
            PermissionName::VIEW_DASHBOARD,
            PermissionName::VIEW_VEHICLES,
            PermissionName::VIEW_RENTALS,
            PermissionName::MANAGE_INSPECTIONS,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param  array<int, PermissionName>  $permissions
     */
    private function syncRole(RoleName $roleName, array $permissions): void
    {
        $role = Role::findOrCreate($roleName->value, 'web');

        $role->syncPermissions(
            array_map(
                static fn (PermissionName $permission): string => $permission->value,
                $permissions,
            ),
        );
    }
}
