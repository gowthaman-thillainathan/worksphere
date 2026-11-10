<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Employee numbers
    |--------------------------------------------------------------------------
    |
    | Generated from a database sequence, e.g. EMP-000421.
    |
    */

    'number' => [
        'prefix' => env('EMPLOYEE_NUMBER_PREFIX', 'EMP-'),
        'padding' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile photos
    |--------------------------------------------------------------------------
    |
    | Stored on a private disk and only served through the authorised API
    | endpoint. Use an S3 disk in deployed environments.
    |
    */

    'photo' => [
        'disk' => env('EMPLOYEE_PHOTO_DISK', 'local'),
        'directory' => 'employees',
        'max_kilobytes' => 2048,
        'min_dimension' => 128,
        'max_dimension' => 4096,
        'mimes' => ['jpeg', 'png', 'webp'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Directory listing
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'max_per_page' => 100,
    ],

];
