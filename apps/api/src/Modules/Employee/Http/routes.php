<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Employee\Http\Controllers\EmployeeAccountController;
use Meridian\Modules\Employee\Http\Controllers\EmployeeController;
use Meridian\Modules\Employee\Http\Controllers\EmployeePhotoController;
use Meridian\Modules\Employee\Http\Controllers\HeadcountController;
use Meridian\Shared\Http\MiddlewareGroup;

/*
|--------------------------------------------------------------------------
| Employee module routes — mounted under config('api.prefix')
|--------------------------------------------------------------------------
*/

$can = static fn (Permission $permission): string => Authorize::using($permission->value);

Route::middleware(MiddlewareGroup::Authenticated->value)
    ->prefix('employees')
    ->name('employees.')
    ->whereUuid('employee')
    ->group(static function () use ($can): void {
        Route::get('/', [EmployeeController::class, 'index'])->middleware($can(Permission::EmployeeView))->name('index');
        Route::get('headcount', HeadcountController::class)->middleware($can(Permission::EmployeeView))->name('headcount');
        Route::post('/', [EmployeeController::class, 'store'])->middleware($can(Permission::EmployeeCreate))->name('store');
        Route::get('{employee}', [EmployeeController::class, 'show'])->middleware($can(Permission::EmployeeView))->name('show');
        Route::patch('{employee}', [EmployeeController::class, 'update'])->middleware($can(Permission::EmployeeUpdate))->name('update');
        Route::delete('{employee}', [EmployeeController::class, 'destroy'])->middleware($can(Permission::EmployeeDelete))->name('destroy');
        Route::post('{employee}/restore', [EmployeeController::class, 'restore'])->middleware($can(Permission::EmployeeDelete))->name('restore');

        Route::get('{employee}/photo', [EmployeePhotoController::class, 'show'])->middleware($can(Permission::EmployeeView))->name('photo.show');
        Route::post('{employee}/photo', [EmployeePhotoController::class, 'store'])->middleware($can(Permission::EmployeeUpdate))->name('photo.store');
        Route::delete('{employee}/photo', [EmployeePhotoController::class, 'destroy'])->middleware($can(Permission::EmployeeUpdate))->name('photo.destroy');

        // Sign-in accounts: user.manage and access.manage (checked again by EmployeeRoleRequest).
        Route::get('{employee}/account', [EmployeeAccountController::class, 'show'])->middleware([$can(Permission::EmployeeView), $can(Permission::UserManage)])->name('account.show');
        Route::post('{employee}/account', [EmployeeAccountController::class, 'store'])->middleware($can(Permission::EmployeeView))->name('account.store');
        Route::put('{employee}/account/role', [EmployeeAccountController::class, 'updateRole'])->middleware($can(Permission::EmployeeView))->name('account.role');
    });
