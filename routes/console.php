<?php

use App\Domain\Automation\Services\AutomationDispatcher;
use App\Models\User;
use App\Support\Commercial\CompanyLifecycle;
use App\Support\Operations\ProductionReadiness;
use App\Support\Operations\StagingGuard;
use App\Support\Security\DemoCredentials;
use App\Support\Security\Mfa\TwoFactorAuthentication;
use Database\Seeders\StagingFictionalSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Command\Command;

Artisan::command('rentadrive:expire-trials', function (CompanyLifecycle $lifecycle): int {
    $count = $lifecycle->expireTrials();
    $this->info('Pruebas vencidas suspendidas: '.$count);

    return Command::SUCCESS;
})->purpose('Suspende de forma idempotente los tenants con prueba vencida.');

Schedule::command('rentadrive:expire-trials')->hourly()->withoutOverlapping();

Artisan::command('rentadrive:production-check', function (ProductionReadiness $readiness): int {
    $problems = [...$readiness->configurationProblems(), ...$readiness->infrastructureProblems()];

    if ($problems !== []) {
        foreach ($problems as $problem) {
            $this->error($problem);
        }

        return Command::FAILURE;
    }

    foreach ($readiness->advisories() as $advisory) {
        $this->warn('Recomendación: '.$advisory);
    }

    $this->info('Configuración básica y conexión SQL Server verificadas. No sustituye auditoría ni prueba de restauración.');

    return Command::SUCCESS;
})->purpose('Valida configuración crítica y conectividad antes de un despliegue.');

Artisan::command('rentadrive:mfa-reset {email}', function (string $email, TwoFactorAuthentication $mfa): int {
    // Recuperación operativa: solo desde consola del servidor, nunca desde la web.
    $user = User::query()->where('email', $email)->first();

    if ($user === null) {
        $this->error('No existe un usuario con ese correo.');

        return Command::FAILURE;
    }

    if (! $user->hasTwoFactorEnabled() && $user->two_factor_secret === null) {
        $this->info('El usuario no tiene MFA configurado.');

        return Command::SUCCESS;
    }

    if (! $this->confirm('¿Desactivar MFA de '.$user->email.'? El usuario deberá volver a activarlo.', false)) {
        $this->warn('Operación cancelada.');

        return Command::FAILURE;
    }

    $mfa->disable($user);
    $this->info('MFA desactivado. Verifica la identidad del usuario por un canal independiente antes de entregarle el acceso.');

    return Command::SUCCESS;
})->purpose('Desactiva MFA de un usuario que perdió su dispositivo y sus códigos de recuperación.');

Artisan::command('rentadrive:automations', function (AutomationDispatcher $dispatcher): int {
    $count = $dispatcher->run();
    $this->info('Automatizaciones enviadas: '.$count);

    return Command::SUCCESS;
})->purpose('Envía avisos operativos multiempresa cuando la configuración lo permite.');

Schedule::command('rentadrive:automations')->dailyAt('08:00')->withoutOverlapping();

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

        return Command::FAILURE;
    }

    $user = User::query()->where('email', $email)->first();

    if ($user !== null && ! $user->isPlatformAdmin() && $user->company_id !== null) {
        $this->error('Ese correo ya pertenece a un usuario tenant y no puede elevarse a SuperAdmin.');

        return Command::FAILURE;
    }

    $user ??= new User;

    $user->forceFill([
        'company_id' => null,
        'branch_id' => null,
        'name' => $name,
        'email' => $email,
        'email_verified_at' => now(),
        'password' => $password,
        'is_active' => true,
        'is_platform_admin' => true,
    ])->save();

    $user->syncRoles([]);

    $this->info('SuperAdmin de plataforma provisionado correctamente.');

    return Command::SUCCESS;
})->purpose('Provisiona o actualiza un SuperAdmin de RentaDrive sin exponer la contraseña.');

Artisan::command('rentadrive:staging-seed {--admin-email=admin@staging.rentadrive.test}', function (): int {
    if (($reason = StagingGuard::refusalReason()) !== null) {
        $this->error($reason);

        return Command::FAILURE;
    }

    $email = (string) $this->option('admin-email');
    $password = (string) config('rentadrive.staging.admin_password');
    if ($password === '') {
        $password = (string) $this->secret('Contraseña del administrador ficticio');
    }

    $validator = Validator::make(
        ['email' => $email, 'password' => $password],
        [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ],
    );

    if ($validator->fails() || DemoCredentials::isKnownDefaultPassword($password)
        || in_array($email, DemoCredentials::KNOWN_DEFAULT_EMAILS, true)) {
        $this->error('Correo o contraseña no válidos (mínimo 12 caracteres con mayúsculas, números y símbolos; no se admiten las cuentas ni contraseñas de demostración).');

        return Command::FAILURE;
    }

    app(StagingFictionalSeeder::class)->configure($email, $password)->run();

    $this->info('Datos ficticios de staging cargados. La contraseña no se muestra ni se registra.');

    return Command::SUCCESS;
})->purpose('Carga una empresa y datos ficticios en una base de staging (rechaza RentaDriveDb).');
