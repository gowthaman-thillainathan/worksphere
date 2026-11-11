<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Support\Facades\Route;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Audit\Http\Controllers\AuditEventController;
use Meridian\Shared\Http\MiddlewareGroup;

/*
|--------------------------------------------------------------------------
| Audit module routes — mounted under config('api.prefix')
|--------------------------------------------------------------------------
*/

Route::get('audit-events', [AuditEventController::class, 'index'])
    ->middleware([MiddlewareGroup::Authenticated->value, Authorize::using(Permission::AuditView->value)])
    ->name('audit.events.index');
