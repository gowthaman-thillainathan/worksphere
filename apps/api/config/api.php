<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Versioned API prefix
    |--------------------------------------------------------------------------
    |
    | Every module mounts its routes below this prefix (docs/05 §1).
    |
    */

    'prefix' => env('API_PREFIX', 'api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Frontend
    |--------------------------------------------------------------------------
    |
    | Base URL of the React SPA. Used to build links in e-mails (password
    | reset, e-mail verification) and post-verification redirects.
    |
    */

    'frontend_url' => rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Request tracing
    |--------------------------------------------------------------------------
    */

    'request_id_header' => 'X-Request-Id',

    /*
    |--------------------------------------------------------------------------
    | Error responses (RFC 9457 Problem Details)
    |--------------------------------------------------------------------------
    */

    'errors' => [
        'type_base_url' => rtrim((string) env('API_ERROR_TYPE_BASE_URL', 'https://docs.meridian.app/errors'), '/'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API documentation (Swagger UI)
    |--------------------------------------------------------------------------
    |
    | The OpenAPI document in packages/api-contract is the source of truth.
    | Swagger UI is served at /docs when enabled (never in production).
    |
    */

    'docs' => [
        'enabled' => (bool) env('API_DOCS_ENABLED', false),
        'spec_path' => env('API_DOCS_SPEC_PATH', base_path('../../packages/api-contract/spec/openapi.yaml')),
        'swagger_ui_version' => '5.17.14',
    ],

];
