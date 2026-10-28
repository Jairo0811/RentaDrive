<?php

declare(strict_types=1);

namespace App\Support\Operations;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

        if (! in_array(strtolower((string) config('database.connections.sqlsrv.encrypt')), ['yes', 'true', '1'], true)
            || in_array(strtolower((string) config('database.connections.sqlsrv.trust_server_certificate')), ['yes', 'true', '1'], true)) {
            $problems[] = 'SQL Server debe usar TLS con verificación de certificado.';
        }

        if (config('session.encrypt') !== true || config('session.http_only') !== true) {
            $problems[] = 'La sesión debe estar cifrada y la cookie debe ser HttpOnly.';
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
        $problems = [];

        try {
            DB::connection()->select('SELECT 1 AS ready');
        } catch (Throwable) {
            $problems[] = 'No se pudo verificar la conexión a la base de datos.';
        }

        $key = 'rentadrive:readiness:'.Str::uuid();
        try {
            Cache::put($key, 'ok', 30);
            if (Cache::get($key) !== 'ok') {
                $problems[] = 'El caché no permite lectura/escritura.';
            }
        } catch (Throwable) {
            $problems[] = 'El caché no está disponible.';
        } finally {
            try {
                Cache::forget($key);
            } catch (Throwable) {
                // La disponibilidad ya se registró.
            }
        }

        $disk = (string) config('rentadrive.evidence.disk', 'local');
        if (! in_array($disk, ['local', 's3'], true)) {
            $problems[] = 'El disco privado de evidencias debe ser local o s3.';
        } else {
            $path = 'rentadrive-health/'.Str::uuid().'.txt';
            try {
                if (! Storage::disk($disk)->put($path, 'ok') || Storage::disk($disk)->get($path) !== 'ok') {
                    $problems[] = 'El disco privado de evidencias no permite lectura/escritura.';
                }
            } catch (Throwable) {
                $problems[] = 'El disco privado de evidencias no está disponible.';
            } finally {
                try {
                    Storage::disk($disk)->delete($path);
                } catch (Throwable) {
                    // El estado de disponibilidad ya se registró.
                }
            }
        }

        return $problems;
    }
}
