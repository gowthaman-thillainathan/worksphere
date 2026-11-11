<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Documents\Http\Controllers\DocumentController;
use Meridian\Shared\Http\MiddlewareGroup;

/*
|--------------------------------------------------------------------------
| Documents module routes — mounted under config('api.prefix')
|--------------------------------------------------------------------------
*/

$can = static fn (Permission $permission): string => Authorize::using($permission->value);

Route::middleware(MiddlewareGroup::Authenticated->value)->group(static function () use ($can): void {
    Route::prefix('employees/{employee}/documents')
        ->whereUuid('employee')
        ->name('employees.documents.')
        ->group(static function () use ($can): void {
            Route::get('/', [DocumentController::class, 'index'])->middleware($can(Permission::DocumentView))->name('index');
            Route::post('/', [DocumentController::class, 'store'])->middleware($can(Permission::DocumentUpload))->name('store');
        });

    Route::prefix('documents/{document}')
        ->whereUuid('document')
        ->name('documents.')
        ->group(static function () use ($can): void {
            Route::get('download', [DocumentController::class, 'download'])->middleware($can(Permission::DocumentView))->name('download');
            Route::delete('/', [DocumentController::class, 'destroy'])->middleware($can(Permission::DocumentDelete))->name('destroy');
        });
});
