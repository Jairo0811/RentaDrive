<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\Security\DemoCredentials;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (($reason = DemoCredentials::refusalReason()) !== null) {
            $this->command?->warn($reason);

            return;
        }

        if (! self::hasConfiguredPassword()) {
            $this->command?->warn('No hay contraseña configurada para la cuenta de demostración; no se crea.');

            return;
        }

        $company = Company::query()->firstOrCreate(
            ['slug' => 'rentadrive-legacy'],
            [
                'name' => 'RentaDrive',
                'legal_name' => 'RentaDrive Legacy',
                'currency' => 'DOP',
                'timezone' => 'America/Santo_Domingo',
                'status' => 'active',
            ],
        );

        $branch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->getKey(),
                'code' => 'PRINCIPAL',
            ],
            [
                'name' => 'Sucursal Principal',
                'is_primary' => true,
                'is_active' => true,
            ],
        );

        $administrator = User::query()->firstOrNew([
            'email' => (string) config('rentadrive.seed.admin_email'),
        ]);

        $administrator->forceFill([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
            'name' => 'Administrador RentaDrive',
            'email_verified_at' => now(),
            'password' => Hash::make((string) config('rentadrive.seed.admin_password')),
            'is_active' => true,
        ])->save();

        $administrator->syncRoles([RoleName::ADMINISTRATOR->value]);
    }

    private static function hasConfiguredPassword(): bool
    {
        return (string) config('rentadrive.seed.admin_password') !== '';
    }
}
