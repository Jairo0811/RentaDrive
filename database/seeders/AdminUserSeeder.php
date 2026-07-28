<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Security\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('El usuario de demostración no se crea fuera de entornos local/testing.');

            return;
        }

        $administrator = User::query()->firstOrNew([
            'email' => (string) config('rentadrive.seed.admin_email'),
        ]);

        $administrator->forceFill([
            'name' => 'Administrador RentaDrive',
            'email_verified_at' => now(),
            'password' => Hash::make((string) config('rentadrive.seed.admin_password')),
            'is_active' => true,
        ])->save();

        $administrator->syncRoles([RoleName::ADMINISTRATOR->value]);
    }
}
