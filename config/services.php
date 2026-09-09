<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have a
    | conventional file to locate the various service credentials.
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

    /*
    |--------------------------------------------------------------------------
    | Paywuz QRIS Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk payment gateway paywuz.id (QRIS)
    | Docs: https://paywuz.id/docs
    |
    */

    'paywuz' => [
        'base_url' => env('PAYWUZ_BASE_URL', 'https://paywuz.id/api/v1'),
        'api_key' => env('PAYWUZ_API_KEY', ''),
        'callback_url' => env('PAYWUZ_CALLBACK_URL', '/webhook/paywuz'),
        'merchant_name' => env('PAYWUZ_MERCHANT_NAME', 'KasirAja'),
    ],

];
