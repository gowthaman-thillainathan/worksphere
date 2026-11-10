<?php

declare(strict_types=1);

use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | The API is stateless: every request is authenticated with a short-lived
    | JWT access token through the "api" guard. See docs/adr/0006.
    |
    */

    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Reset
    |--------------------------------------------------------------------------
    |
    | Reset tokens are single-use, hashed at rest and expire after "expire"
    | minutes. "throttle" is the minimum number of seconds between two reset
    | e-mails for the same account.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => (int) env('AUTH_PASSWORD_RESET_EXPIRE', 30),
            'throttle' => (int) env('AUTH_PASSWORD_RESET_THROTTLE', 60),
        ],
    ],

    'password_timeout' => (int) env('AUTH_PASSWORD_TIMEOUT', 10800),

];
