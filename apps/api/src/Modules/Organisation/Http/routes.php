<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Organisation\Http\Controllers\DepartmentController;
use Meridian\Shared\Http\MiddlewareGroup;

/*
|--------------------------------------------------------------------------
| Organisation module routes — mounted under config('api.prefix')
|--------------------------------------------------------------------------
*/

$can = static fn (Permission $permission): string => Authorize::using($permission->value);

Route::middleware(MiddlewareGroup::Authenticated->value)
    ->prefix('departments')
    ->name('departments.')
    ->whereUuid('department')
    ->group(static function () use ($can): void {
        Route::get('/', [DepartmentController::class, 'index'])->middleware($can(Permission::OrganisationView))->name('index');
        Route::post('/', [DepartmentController::class, 'store'])->middleware($can(Permission::OrganisationManage))->name('store');
        Route::get('{department}', [DepartmentController::class, 'show'])->middleware($can(Permission::OrganisationView))->name('show');
        Route::patch('{department}', [DepartmentController::class, 'update'])->middleware($can(Permission::OrganisationManage))->name('update');
        Route::delete('{department}', [DepartmentController::class, 'destroy'])->middleware($can(Permission::OrganisationManage))->name('destroy');
    });
