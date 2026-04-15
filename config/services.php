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

    'mypos' => [
        'client_id' => env('MYPOS_CLIENT_ID'),
        'client_secret' => env('MYPOS_CLIENT_SECRET'),
        'partner_client_id' => env('MYPOS_PARTNER_CLIENT_ID'),
        'partner_client_secret' => env('MYPOS_PARTNER_CLIENT_SECRET'),
        'partner_id' => env('MYPOS_PARTNER_ID'),
        'application_id' => env('MYPOS_APPLICATION_ID'),
        'is_demo' => env('MYPOS_IS_DEMO', false),
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

    'boc' => [
        'client_id' => env('BOC_CLIENT_ID'),
        'client_secret' => env('BOC_CLIENT_SECRET'),
        'is_demo' => env('BOC_IS_DEMO', true),
    ],

    'eurobank' => [
        'client_id' => env('EUROBANK_CLIENT_ID'),
        'client_secret' => env('EUROBANK_CLIENT_SECRET'),
        'is_demo' => env('EUROBANK_IS_DEMO', true),
    ],

];
