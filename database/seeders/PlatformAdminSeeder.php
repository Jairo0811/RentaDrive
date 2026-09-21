<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('El SuperAdmin de plataforma no se crea fuera de entornos local/testing.');

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
}
