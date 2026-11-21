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
    ],
];
