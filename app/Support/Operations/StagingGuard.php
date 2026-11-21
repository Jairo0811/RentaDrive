<?php

declare(strict_types=1);

namespace App\Support\Operations;

/**
 * Evita que los datos ficticios de staging se carguen en una base que no sea de staging/pruebas.
 * Rechaza siempre RentaDriveDb y exige una confirmación explícita por entorno.
 */
final class StagingGuard
{
    private const ALLOWED_DATABASE = '/^RentaDrive(Staging|E2E|Test)[A-Za-z0-9_]*$/i';

    /** @return string|null Motivo del rechazo; null si se permite sembrar datos ficticios. */
    public static function refusalReason(): ?string
    {
        if (config('rentadrive.staging.allow_seed') !== true) {
            return 'Define RENTADRIVE_ALLOW_STAGING_SEED=true para confirmar que este entorno es de staging.';
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (strcasecmp($database, 'RentaDriveDb') === 0) {
            return 'Prohibido: la base RentaDriveDb nunca recibe datos ficticios de staging.';
        }

        if ($connection === 'sqlite' && $database === ':memory:' && app()->environment('testing')) {
            return null;
        }

        if (preg_match(self::ALLOWED_DATABASE, $database) !== 1) {
            return "La base '{$database}' no parece de staging (RentaDriveStaging*, RentaDriveE2E*, RentaDriveTest*).";
        }

        return null;
    }
}
