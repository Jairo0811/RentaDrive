<?php

declare(strict_types=1);

namespace App\Support\Security;

/**
 * Controla cuándo pueden crearse cuentas de demostración con credenciales previsibles.
 *
 * Los seeders de usuarios solo operan en local/testing. Además, en "local" exigen que APP_URL apunte a
 * un host de desarrollo: un staging mal configurado con APP_ENV=local no debe poder crear administradores previsibles.
 */
final class DemoCredentials
{
    /** Contraseñas de demostración publicadas en .env.example. Nunca deben existir en staging ni producción. */
    public const KNOWN_DEFAULT_PASSWORDS = [
        'RentaDrive123..',
        'RentaDrivePlatform123!',
    ];

    /** Correos de demostración previsibles. */
    public const KNOWN_DEFAULT_EMAILS = [
        'admin@rentadrive.com.do',
        'platform@rentadrive.test',
    ];

    /** @return string|null Motivo por el que NO se permite crear cuentas de demostración; null si se permite. */
    public static function refusalReason(): ?string
    {
        if (! app()->environment(['local', 'testing'])) {
            return 'Las cuentas de demostración no se crean fuera de entornos local/testing.';
        }

        if (app()->environment('local') && ! self::isDevelopmentUrl((string) config('app.url'))) {
            return 'APP_ENV=local con APP_URL no local: se rechaza crear cuentas de demostración (parece un staging mal configurado).';
        }

        return null;
    }

    public static function isDevelopmentUrl(string $url): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        $host = trim($host, '[]');

        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return true;
        }

        foreach (['.localhost', '.test'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }

    public static function isKnownDefaultPassword(string $password): bool
    {
        return in_array($password, self::KNOWN_DEFAULT_PASSWORDS, true);
    }
}
