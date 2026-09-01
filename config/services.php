<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
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

    'waty' => [
        'base_url' => env('WATY_WHATSAPP_BASE_URL', env('WATY_API_BASE_URL', 'https://bizlawn.storesite.in/api')),
        'api_token' => env('WATY_WHATSAPP_API_TOKEN', env('WATY_API_TOKEN')),
        'otp_account' => env('WATY_WHATSAPP_OTP_ACCOUNT', env('WATY_OTP_ACCOUNT', 'sa_otp_code')),
        'admin_phone_number' => env('WATY_WHATSAPP_ADMIN_PHONE_NUMBER', env('WATY_ADMIN_PHONE_NUMBER', '+919911041964')),
        'timeout' => (int) env('WATY_WHATSAPP_TIMEOUT', 15),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'kanodia-textiles'),
        'server_key' => env('FIREBASE_SERVER_KEY'),
        'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', storage_path('app/firebase-credentials.json')),
    ],

];
