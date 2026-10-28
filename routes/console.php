<?php

use App\Domain\Automation\Services\AutomationDispatcher;
use App\Models\User;
use App\Support\Commercial\CompanyLifecycle;
use App\Support\Operations\ProductionReadiness;
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

    $this->info('Configuración básica y conexión SQL Server verificadas. No sustituye auditoría ni prueba de restauración.');

    return Command::SUCCESS;
})->purpose('Valida configuración crítica y conectividad antes de un despliegue.');

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
