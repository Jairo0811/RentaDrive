<?php

declare(strict_types=1);

return [
    'trial_days' => 14,

    'vehicle_photos' => [
        // Fotos del catálogo de flota (no son datos personales). 'public' requiere `php artisan storage:link`.
        'disk' => env('RENTA_VEHICLE_PHOTO_DISK', 'public'),
        'max_kb' => 4096,
    ],

    'evidence' => [
        'disk' => env('RENTA_EVIDENCE_DISK', 'local'),
    ],

    'automation' => [
        'enabled' => (bool) env('RENTA_AUTOMATION_ENABLED', false),
    ],

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'max_users' => 5,
            'max_branches' => 2,
            'max_vehicles' => 25,
        ],
        'professional' => [
            'name' => 'Professional',
            'max_users' => 15,
            'max_branches' => 5,
            'max_vehicles' => 100,
        ],
        'business' => [
            'name' => 'Business',
            'max_users' => 50,
            'max_branches' => 20,
            'max_vehicles' => 500,
        ],
    ],

    // Solo para seeders local/testing. Fuera de esos entornos NO existe contraseña por defecto
    // (ver App\Support\Security\DemoCredentials).
    'seed' => [
        'admin_email' => env('RENTADRIVE_ADMIN_EMAIL', 'admin@rentadrive.com.do'),
        'admin_password' => env('RENTADRIVE_ADMIN_PASSWORD', in_array(env('APP_ENV'), ['local', 'testing'], true) ? 'RentaDrive123..' : null),
        'platform_admin_email' => env('RENTADRIVE_PLATFORM_ADMIN_EMAIL', 'platform@rentadrive.test'),
        'platform_admin_password' => env('RENTADRIVE_PLATFORM_ADMIN_PASSWORD', in_array(env('APP_ENV'), ['local', 'testing'], true) ? 'RentaDrivePlatform123!' : null),
    ],

    'security' => [
        // Proxies de confianza (IPs/CIDR separados por coma). Nunca '*': ver ProductionReadiness.
        'trusted_proxies' => env('TRUSTED_PROXIES'),

        // Content-Security-Policy (ver docs/csp.md). mode: enforce | report-only | off.
        'csp' => [
            'mode' => env('CSP_MODE', 'enforce'),
            // Orígenes adicionales (https://host, separados por coma) para imágenes y conexiones fetch/XHR.
            'img' => env('CSP_IMG_SRC', ''),
            'connect' => env('CSP_CONNECT_SRC', ''),
        ],

        // MFA TOTP para SuperAdmin y Administradores. enforce=true obliga a activarlo antes de usar el panel.
        'mfa' => [
            'enforce' => (bool) env('MFA_ENFORCE', false),
            'issuer' => env('MFA_ISSUER', 'RentaDrive'),
        ],
    ],

    // Observabilidad y alertas (ver docs/operations-resilience.md).
    'operations' => [
        // Token Bearer para /api/health/details (≥ 32 caracteres; vacío = endpoint inexistente).
        'health_token' => env('HEALTH_TOKEN'),
        'max_queue_age_seconds' => (int) env('HEALTH_MAX_QUEUE_AGE', 900),
        'max_failed_jobs' => (int) env('HEALTH_MAX_FAILED_JOBS', 5),
        'max_scheduler_age_seconds' => (int) env('HEALTH_MAX_SCHEDULER_AGE', 180),
        // En producción el scheduler es obligatorio; en local/testing no.
        'require_scheduler' => (bool) env('HEALTH_REQUIRE_SCHEDULER', env('APP_ENV') === 'production'),
        // Alertas por correo solo si se configura un destinatario (y un proveedor de correo real).
        'alert_email' => env('RENTADRIVE_ALERT_EMAIL'),
    ],

    // Datos ficticios de staging (ver App\Support\Operations\StagingGuard).
    'staging' => [
        'allow_seed' => (bool) env('RENTADRIVE_ALLOW_STAGING_SEED', false),
        'admin_password' => env('RENTADRIVE_STAGING_ADMIN_PASSWORD'),
    ],
];
