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

    'seed' => [
        'admin_email' => env('RENTADRIVE_ADMIN_EMAIL', 'admin@rentadrive.com.do'),
        'admin_password' => env('RENTADRIVE_ADMIN_PASSWORD', 'RentaDrive123..'),
        'platform_admin_email' => env('RENTADRIVE_PLATFORM_ADMIN_EMAIL', 'platform@rentadrive.test'),
        'platform_admin_password' => env('RENTADRIVE_PLATFORM_ADMIN_PASSWORD', 'RentaDrivePlatform123!'),
    ],
];
