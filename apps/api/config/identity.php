<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | JWT access tokens
    |--------------------------------------------------------------------------
    |
    | Short-lived, stateless bearer tokens. The secret must be at least 256 bits;
    | generate one with:
    |   php -r "echo 'base64:'.base64_encode(random_bytes(64)).PHP_EOL;"
    |
    */

    'jwt' => [
        'secret' => env('JWT_SECRET'),
        'algorithm' => 'HS256',
        'key_id' => env('JWT_KEY_ID', 'v1'),
        'issuer' => env('JWT_ISSUER', env('APP_URL', 'http://localhost')),
        'audience' => env('JWT_AUDIENCE', 'meridian-api'),
        'ttl_seconds' => (int) env('JWT_ACCESS_TOKEN_TTL', 900),
        'leeway_seconds' => (int) env('JWT_LEEWAY', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refresh tokens
    |--------------------------------------------------------------------------
    |
    | Opaque, single-use, rotated on every refresh and stored as SHA-256
    | hashes. Delivered to the SPA only in an HttpOnly cookie scoped to the
    | auth endpoints.
    |
    */

    'refresh_token' => [
        'ttl_seconds' => (int) env('REFRESH_TOKEN_TTL', 1_209_600),
        'entropy_bytes' => 64,
        'prune_after_days' => 30,
        'cookie' => [
            'name' => env('REFRESH_TOKEN_COOKIE', 'refresh_token'),
            'path' => '/'.trim((string) env('API_PREFIX', 'api/v1'), '/').'/auth',
            'domain' => env('REFRESH_TOKEN_COOKIE_DOMAIN'),
            'secure' => (bool) env('REFRESH_TOKEN_COOKIE_SECURE', true),
            'same_site' => 'strict',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Brute-force protection
    |--------------------------------------------------------------------------
    */

    'lockout' => [
        'max_attempts' => (int) env('AUTH_LOCKOUT_MAX_ATTEMPTS', 5),
        'duration_seconds' => (int) env('AUTH_LOCKOUT_DURATION', 900),
    ],

    // Per minute. Raise only for automated test environments (E2E suites sign in many times).
    'rate_limits' => [
        'login_per_account' => (int) env('AUTH_RATE_LIMIT_LOGIN_PER_ACCOUNT', 5),
        'login_per_ip' => (int) env('AUTH_RATE_LIMIT_LOGIN_PER_IP', 20),
        'password_reset_per_ip' => (int) env('AUTH_RATE_LIMIT_PASSWORD_RESET_PER_IP', 5),
        'refresh_per_ip' => (int) env('AUTH_RATE_LIMIT_REFRESH_PER_IP', 30),
        'verification_per_user' => (int) env('AUTH_RATE_LIMIT_VERIFICATION_PER_USER', 6),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password policy (NIST 800-63B)
    |--------------------------------------------------------------------------
    */

    'password' => [
        'min_length' => 12,
        'max_length' => 1024,
        'check_compromised' => (bool) env('PASSWORD_CHECK_COMPROMISED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | E-mail verification
    |--------------------------------------------------------------------------
    */

    'email_verification' => [
        'expire_minutes' => (int) env('EMAIL_VERIFICATION_EXPIRE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend routes used in e-mails and redirects
    |--------------------------------------------------------------------------
    */

    'frontend_paths' => [
        'forgot_password' => '/forgot-password',
        'reset_password' => '/reset-password',
        'email_verified' => '/verify-email?status=verified',
        'email_verification_failed' => '/verify-email?status=failed',
    ],

];
