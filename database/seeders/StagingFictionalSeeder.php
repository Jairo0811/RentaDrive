<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Security\Enums\RoleName;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Empresa y datos TOTALMENTE ficticios para staging. La contraseña llega desde fuera (nunca por defecto).
 * Se ejecuta solo mediante `rentadrive:staging-seed`, que aplica App\Support\Operations\StagingGuard.
 */
final class StagingFictionalSeeder extends Seeder
{
    public const COMPANY_SLUG = 'staging-ficticia';

    private string $email = 'admin@staging.rentadrive.test';

    private string $password = '';

    public function configure(string $email, string $password): static
    {
        $this->email = $email;
        $this->password = $password;

        return $this;
    }

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $company = Company::query()->firstOrCreate(
            ['slug' => self::COMPANY_SLUG],
            [
                'name' => 'Empresa Ficticia (staging)',
                'legal_name' => 'Empresa Ficticia de Prueba',
                'currency' => 'DOP',
                'timezone' => 'America/Santo_Domingo',
                'status' => 'active',
            ],
        );

        $branch = Branch::query()->firstOrCreate(
            ['company_id' => $company->getKey(), 'code' => 'PRINCIPAL'],
            ['name' => 'Sucursal Ficticia', 'is_primary' => true, 'is_active' => true],
        );

        $admin = User::query()->firstOrNew(['email' => $this->email]);
        $admin->forceFill([
            'company_id' => $company->getKey(),
            'branch_id' => $branch->getKey(),
            'name' => 'Administrador Ficticio',
            'email_verified_at' => now(),
            'password' => Hash::make($this->password),
            'is_active' => true,
        ])->save();
        $admin->syncRoles([RoleName::ADMINISTRATOR->value]);

        (new OperationalReferenceSeeder)->forCompanySlug(self::COMPANY_SLUG)->run();
    }
}
