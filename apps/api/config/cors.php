<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing
    |--------------------------------------------------------------------------
    |
    | The SPA (FRONTEND_URL) calls the API from another origin in deployed
    | environments. Credentials must be allowed so the browser sends the
    | HttpOnly refresh-token cookie to /auth/*; that in turn requires an
    | explicit origin (never "*"). Exposed headers let the SPA read ETags for
    | optimistic locking and request ids for support.
    |
    | Locally the Vite dev server proxies /api, so requests are same-origin.
    |
    */

    'paths' => [trim((string) env('API_PREFIX', 'api/v1'), '/').'/*'],

    'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173'))))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'If-Match', 'X-Request-Id', 'X-Requested-With'],

    'exposed_headers' => ['ETag', 'Location', 'Retry-After', 'X-Request-Id', 'RateLimit-Limit', 'RateLimit-Remaining'],

    'max_age' => 600,

    'supports_credentials' => true,

];
