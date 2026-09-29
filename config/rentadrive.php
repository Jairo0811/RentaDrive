<?php

declare(strict_types=1);

return [
    'trial_days' => 14,
    'subscription_grace_days' => 7,

    'storage' => [
        'public_disk' => env('RENTADRIVE_PUBLIC_DISK', 'public'),
        'private_disk' => env('RENTADRIVE_PRIVATE_DISK', 'local'),
        'backup_disk' => env('RENTADRIVE_BACKUP_DISK', env('RENTADRIVE_PRIVATE_DISK', 'local')),
    ],

    'health' => [
        'ops_email' => env('RENTADRIVE_OPS_EMAIL'),
        'max_failed_jobs' => (int) env('RENTADRIVE_MAX_FAILED_JOBS', 25),
        'max_backup_age_hours' => (int) env('RENTADRIVE_MAX_BACKUP_AGE_HOURS', 30),
    ],

    'backup' => [
        'retention_days' => (int) env('RENTADRIVE_BACKUP_RETENTION_DAYS', 14),
        'require_external_in_production' => (bool) env('RENTADRIVE_BACKUP_REQUIRE_EXTERNAL', true),
        'tables' => [
            'companies',
            'branches',
            'users',
            'permissions',
            'roles',
            'role_has_permissions',
            'model_has_permissions',
            'model_has_roles',
            'vehicle_brands',
            'vehicle_categories',
            'vehicle_models',
            'customers',
            'vehicles',
            'vehicle_maintenances',
            'reservations',
            'rentals',
            'inspections',
            'rental_signatures',
            'invoices',
            'payments',
            'payment_intents',
            'payment_refunds',
            'payment_webhook_events',
            'fiscal_sequences',
            'automation_deliveries',
            'settings',
            'audit_logs',
            'subscriptions',
        ],
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
