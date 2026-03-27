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
        'https://app.agenchq.com',
        'https://agenchq.com',
        env('VERCEL_URL') ? ('https://' . ltrim(env('VERCEL_URL'), '/')) : null,
    ]),

    'allowed_origins_patterns' => [
        // Allow all agenchq.com subdomains (tenant subdomains like jimmy.agenchq.com)
        '#^https://.*\.agenchq\.com$#',
        // Allow Vercel preview deployments
        '#^https://.*\.vercel\.app$#',
        // Development localhost patterns
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#',
        '#^http://192\.168\.\d+\.\d+:\d+$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];

