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
    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
    ],
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

    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_SERVER_API_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    ],

    'weather' => [
        'url' => env(
            'WEATHER_API_URL',
            'https://api.open-meteo.com/v1/forecast'
        ),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env(
            'GROQ_MODEL',
            'openai/gpt-oss-20b'
        ),
    ],

    'openrouter' => [
    'api_key' => env('OPENROUTER_API_KEY'),
    'model' => env(
        'OPENROUTER_MODEL',
        'openrouter/free'
    ),
],

];
