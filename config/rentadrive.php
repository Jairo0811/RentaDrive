<?php

declare(strict_types=1);

return [
    'seed' => [
        'admin_email' => env('RENTADRIVE_ADMIN_EMAIL', 'admin@rentadrive.com.do'),
        'admin_password' => env('RENTADRIVE_ADMIN_PASSWORD', 'RentaDrive123..'),
        'platform_admin_email' => env('RENTADRIVE_PLATFORM_ADMIN_EMAIL', 'platform@rentadrive.test'),
        'platform_admin_password' => env('RENTADRIVE_PLATFORM_ADMIN_PASSWORD', 'RentaDrivePlatform123!'),
    ],
];
