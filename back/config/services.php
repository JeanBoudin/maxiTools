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

    'twitch' => [
        'client_id' => env('TWITCH_CLIENT_ID'),
        'client_secret' => env('TWITCH_CLIENT_SECRET'),
        'redirect_uri' => env('TWITCH_REDIRECT_URI'),
        'scopes' => array_filter(explode(' ', env('TWITCH_SCOPES', 'channel:read:subscriptions'))),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
        'frontend_redirect_path' => env('FRONTEND_OAUTH_REDIRECT_PATH', '/auth/callback'),
    ],

    'spotify' => [
        'client_id' => env('SPOTIFY_CLIENT_ID'),
        'client_secret' => env('SPOTIFY_CLIENT_SECRET'),
        'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),
        'scopes' => array_filter(explode(' ', env('SPOTIFY_SCOPES', 'user-read-currently-playing user-read-playback-state'))),
        'frontend_url' => env('FRONTEND_URL', 'http://localhost:5173'),
    ],

];
