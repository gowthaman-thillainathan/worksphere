<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Employee documents
    |--------------------------------------------------------------------------
    |
    | Stored on a private disk and only served through the authorised API
    | endpoint. Use an S3 disk in deployed environments.
    |
    */

    'disk' => env('DOCUMENTS_DISK', 'local'),
    'directory' => 'documents',
    'max_kilobytes' => 10240,
    'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'],

];
