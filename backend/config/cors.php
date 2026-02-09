<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://cpdemo.blueinctech.com',
        env('VERCEL_URL'), // Vercel deployment URL
    ]),

    'allowed_origins_patterns' => array_merge(
        [],
        env('APP_ENV', 'local') === 'production' ? [] : [
            '#^http://localhost:\\d+$#', // Allow any localhost port for development
            '#^http://127\\.0\\.0\\.1:\\d+$#', // Allow any 127.0.0.1 port for development
            '#^http://192\\.168\\.\\d+\\.\\d+:\\d+$#', // Allow any local network IP (192.168.x.x)
            '#^http://10\\.\\d+\\.\\d+\\.\\d+:\\d+$#', // Allow any local network IP (10.x.x.x)
            '#^http://172\\.(1[6-9]|2[0-9]|3[0-1])\\.\\d+\\.\\d+:\\d+$#', // Allow any local network IP (172.16-31.x.x)
        ]
    ),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

