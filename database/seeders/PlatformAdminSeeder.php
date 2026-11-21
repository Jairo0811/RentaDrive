<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Support\Security\DemoCredentials;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class PlatformAdminSeeder extends Seeder
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

        $user = User::query()->firstOrNew([
            'email' => (string) config('rentadrive.seed.platform_admin_email'),
        ]);

        $user->forceFill([
            'company_id' => null,
            'branch_id' => null,
            'name' => 'SuperAdmin RentaDrive',
            'email_verified_at' => now(),
            'password' => Hash::make((string) config('rentadrive.seed.platform_admin_password')),
            'is_active' => true,
            'is_platform_admin' => true,
        ])->save();

        $user->syncRoles([]);
    }

    private static function hasConfiguredPassword(): bool
    {
        return (string) config('rentadrive.seed.platform_admin_password') !== '';
    }
}
