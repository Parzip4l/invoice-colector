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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'microsoft_sso' => [
        'enabled' => env('MICROSOFT_SSO_ENABLED', true),
        'tenant_id' => env('AZURE_TENANT_ID', env('MICROSOFT_TENANT_ID')),
        'client_id' => env('AZURE_CLIENT_ID', env('MICROSOFT_CLIENT_ID')),
        'client_secret' => env('AZURE_CLIENT_SECRET', env('MICROSOFT_CLIENT_SECRET')),
        'redirect_uri' => env('AZURE_REDIRECT_URI', env('MICROSOFT_REDIRECT_URI')),
        'allowed_domain' => env('MICROSOFT_ALLOWED_DOMAIN', 'lrtjakarta.co.id'),
        'domain_hint' => env('MICROSOFT_DOMAIN_HINT', 'lrtjakarta.co.id'),
        'prompt' => env('MICROSOFT_PROMPT'),
        'scopes' => env('MICROSOFT_SCOPES', 'openid profile email User.Read'),
        'timeout' => (int) env('MICROSOFT_TIMEOUT', 20),
    ],

];
