<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Http\Controllers\PermissionController;
use Meridian\Modules\Access\Http\Controllers\RoleController;
use Meridian\Shared\Http\MiddlewareGroup;

/*
|--------------------------------------------------------------------------
| Access module routes — mounted under config('api.prefix')
|--------------------------------------------------------------------------
*/

Route::middleware([MiddlewareGroup::Authenticated->value, Authorize::using(Permission::AccessManage->value)])
    ->group(static function (): void {
        Route::get('permissions', [PermissionController::class, 'index'])->name('access.permissions.index');
        Route::get('roles', [RoleController::class, 'index'])->name('access.roles.index');
    });
