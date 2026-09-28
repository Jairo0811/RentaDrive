<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'graph_version' => env('WHATSAPP_GRAPH_VERSION'),
        'booking_template' => env('WHATSAPP_BOOKING_TEMPLATE'),
        'booking_template_language' => env('WHATSAPP_BOOKING_TEMPLATE_LANGUAGE', 'es'),
    ],

    'payment_gateway' => [
        'base_url' => env('PAYMENT_GATEWAY_BASE_URL'),
        'token' => env('PAYMENT_GATEWAY_TOKEN'),
        'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
        'timeout' => env('PAYMENT_GATEWAY_TIMEOUT', 15),
    ],

    'fiscal_gateway' => [
        'base_url' => env('FISCAL_GATEWAY_BASE_URL'),
        'token' => env('FISCAL_GATEWAY_TOKEN'),
        'timeout' => env('FISCAL_GATEWAY_TIMEOUT', 20),
    ],

];
