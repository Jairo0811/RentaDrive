<?php

use App\Jobs\ScanOperationalAlertsJob;
use App\Models\BackupSnapshot;
use App\Models\User;
use App\Support\Production\BackupManager;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\Console\Command\Command;

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

Artisan::command('rentadrive:automation-scan', function () {
    ScanOperationalAlertsJob::dispatchSync();
    $this->info('Escaneo de automatizaciones completado.');

    return Command::SUCCESS;
})->purpose('Escanea reservas, devoluciones, mantenimientos y documentos próximos a vencer.');

Artisan::command('rentadrive:backup:create', function (BackupManager $backups) {
    $snapshot = $backups->create(auth()->id());

    $this->info('Backup creado y verificado: #'.$snapshot->getKey().' · '.$snapshot->path);

    return Command::SUCCESS;
})->purpose('Crea un backup cifrado y verificable del dominio SaaS.');

Artisan::command('rentadrive:backup:verify {snapshot}', function (BackupManager $backups, int $snapshot) {
    $record = BackupSnapshot::query()->findOrFail($snapshot);
    $backups->verify($record);

    $this->info('Backup #'.$record->getKey().' verificado correctamente.');

    return Command::SUCCESS;
})->purpose('Verifica existencia, checksum, cifrado y formato de un backup.');

Artisan::command('rentadrive:backup:restore {snapshot} {--force} {--allow-production}', function (BackupManager $backups, int $snapshot) {
    if (app()->environment('production') && ! $this->option('allow-production')) {
        $this->error('En producción debes añadir --allow-production explícitamente.');

        return Command::FAILURE;
    }

    if (! $this->option('force') && ! $this->confirm('Esta operación reemplazará los datos de negocio actuales. ¿Continuar?')) {
        $this->warn('Restauración cancelada.');

        return Command::SUCCESS;
    }

    $record = BackupSnapshot::query()->findOrFail($snapshot);
    $backups->restore($record);

    $this->info('Backup #'.$record->getKey().' restaurado.');

    return Command::SUCCESS;
})->purpose('Restaura un backup cifrado de RentaDrive con confirmación explícita.');
