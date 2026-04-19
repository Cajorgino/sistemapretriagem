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

    /*
    | WAHA — WhatsApp HTTP API (devlikeapro/waha)
    | Docs: https://waha.devlike.pro/docs/how-to/send-messages/
    | POST {base_url}/api/sendText — JSON: session, chatId (5511...@c.us), text
    | Header X-Api-Key: use WAHA_API_KEY (ou legado WAHA_API_TOKEN), salvo se WAHA_NO_API_KEY=true
    | Base URL: WAHA_BASE_URL (preferido) ou WAHA_API_URL
    | Sessão: WAHA_SESSION_NAME (preferido) ou WAHA_SESSION
    */
    'waha' => [
        'base_url' => env('WAHA_BASE_URL', env('WAHA_API_URL')),
        'api_token' => env('WAHA_API_KEY', env('WAHA_API_TOKEN')),
        'session' => env('WAHA_SESSION_NAME', env('WAHA_SESSION', 'default')),
        'no_api_key' => filter_var(env('WAHA_NO_API_KEY', false), FILTER_VALIDATE_BOOLEAN),
        'enabled' => env('WAHA_ENABLED') !== null
            ? filter_var(env('WAHA_ENABLED'), FILTER_VALIDATE_BOOLEAN)
            : (filled(env('WAHA_BASE_URL')) || filled(env('WAHA_API_URL'))),
        'timeout' => (int) env('WAHA_HTTP_TIMEOUT', 20),
        'webhook_secret' => env('WAHA_WEBHOOK_SECRET'),
    ],

];
