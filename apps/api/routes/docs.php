<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Meridian\Shared\Http\Controllers\ApiDocumentationController;

/*
|--------------------------------------------------------------------------
| API documentation
|--------------------------------------------------------------------------
|
| Swagger UI over the OpenAPI contract in packages/api-contract. Disabled
| unless API_DOCS_ENABLED=true; keep it disabled in production.
|
*/

if (config('api.docs.enabled')) {
    Route::get('docs', [ApiDocumentationController::class, 'ui'])->name('docs.ui');
    Route::get('docs/openapi.yaml', [ApiDocumentationController::class, 'spec'])->name('docs.spec');
}
