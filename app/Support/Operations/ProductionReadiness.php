<?php

declare(strict_types=1);

namespace App\Support\Operations;

use Illuminate\Support\Facades\DB;
use Throwable;

final class ProductionReadiness
{
    /** @return list<string> */
    public function configurationProblems(): array
    {
        $problems = [];

        if (config('app.env') !== 'production') {
            $problems[] = 'APP_ENV debe ser production.';
        }

        if (config('app.debug') !== false) {
            $problems[] = 'APP_DEBUG debe estar deshabilitado.';
        }

        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $problems[] = 'APP_URL debe utilizar HTTPS.';
        }

        if (! is_string(config('app.key')) || config('app.key') === '') {
            $problems[] = 'APP_KEY debe configurarse de forma segura.';
        }

        if (config('session.secure') !== true) {
            $problems[] = 'SESSION_SECURE_COOKIE debe ser true.';
        }

        if (config('database.default') !== 'sqlsrv') {
            $problems[] = 'La base de datos de producción debe ser SQL Server.';
        }

        if (in_array(config('queue.default'), ['sync', 'null', 'deferred', 'background'], true)) {
            $problems[] = 'QUEUE_CONNECTION debe utilizar un backend de trabajos persistente.';
        }

        if (in_array(config('cache.default'), ['array', 'null'], true)) {
            $problems[] = 'CACHE_STORE debe utilizar un backend persistente.';
        }

        if (config('rentadrive.automation.enabled')
            && in_array(config('mail.default'), ['log', 'array'], true)) {
            $problems[] = 'Las automatizaciones necesitan un proveedor real de correo.';
        }

        return $problems;
    }

    /** @return list<string> */
    public function infrastructureProblems(): array
    {
        try {
            DB::connection()->select('SELECT 1 AS ready');
        } catch (Throwable) {
            return ['No se pudo verificar la conexión a la base de datos.'];
        }

        return [];
    }
}
