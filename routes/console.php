<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('rentadrive:platform-admin {email} {--name=SuperAdmin RentaDrive}', function (string $email) {
    $name = (string) $this->option('name');
    $password = (string) $this->secret('Contraseña del SuperAdmin');
    $confirmation = (string) $this->secret('Confirma la contraseña');

    $validator = Validator::make([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $confirmation,
    ], [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    $user = User::query()->where('email', $email)->first();

    if ($user !== null && ! $user->isPlatformAdmin() && $user->company_id !== null) {
        $this->error('Ese correo ya pertenece a un usuario tenant y no puede elevarse a SuperAdmin.');

        return self::FAILURE;
    }

    $user ??= new User;

    $user->forceFill([
        'company_id' => null,
        'branch_id' => null,
        'name' => $name,
        'email' => $email,
        'email_verified_at' => now(),
        'password' => Hash::make($password),
        'is_active' => true,
        'is_platform_admin' => true,
    ])->save();

    $user->syncRoles([]);

    $this->info('SuperAdmin de plataforma provisionado correctamente.');

    return self::SUCCESS;
})->purpose('Provisiona o actualiza un SuperAdmin de RentaDrive sin exponer la contraseña.');
