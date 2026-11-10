<?php

declare(strict_types=1);

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Meridian\Modules\Identity\Http\Controllers\AuthenticationController;
use Meridian\Modules\Identity\Http\Controllers\CurrentUserController;
use Meridian\Modules\Identity\Http\Controllers\EmailVerificationController;
use Meridian\Modules\Identity\Http\Controllers\PasswordResetController;
use Meridian\Modules\Identity\Http\RateLimiterName;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Security\BearerTokenAuthenticator;

/*
|--------------------------------------------------------------------------
| Identity module routes — mounted under config('api.prefix')
|--------------------------------------------------------------------------
|
| Routes that an unverified user must still reach (logout, resend link, /me)
| use plain authentication; everything else in the API uses the
| "authenticated" group, which also requires a verified e-mail address.
|
*/

$authenticated = Authenticate::using(BearerTokenAuthenticator::GUARD);

Route::prefix('auth')->group(static function () use ($authenticated): void {
    Route::post('login', [AuthenticationController::class, 'login'])
        ->middleware(ThrottleRequests::using(RateLimiterName::Login->value))
        ->name(RouteName::Login->value);

    Route::post('refresh', [AuthenticationController::class, 'refresh'])
        ->middleware(ThrottleRequests::using(RateLimiterName::Refresh->value))
        ->name(RouteName::Refresh->value);

    Route::post('forgot-password', [PasswordResetController::class, 'forgot'])
        ->middleware(ThrottleRequests::using(RateLimiterName::PasswordReset->value))
        ->name(RouteName::ForgotPassword->value);

    Route::post('reset-password', [PasswordResetController::class, 'reset'])
        ->middleware(ThrottleRequests::using(RateLimiterName::PasswordReset->value))
        ->name(RouteName::ResetPassword->value);

    Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(ThrottleRequests::using(RateLimiterName::EmailVerification->value))
        ->whereUuid('id')
        ->name(RouteName::VerifyEmail->value);

    Route::middleware($authenticated)->group(static function (): void {
        Route::post('logout', [AuthenticationController::class, 'logout'])
            ->name(RouteName::Logout->value);

        Route::post('logout-all', [AuthenticationController::class, 'logoutEverywhere'])
            ->name(RouteName::LogoutEverywhere->value);

        Route::post('email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->middleware(ThrottleRequests::using(RateLimiterName::EmailVerification->value))
            ->name(RouteName::ResendEmailVerification->value);
    });
});

Route::get('me', [CurrentUserController::class, 'show'])
    ->middleware($authenticated)
    ->name(RouteName::CurrentUser->value);
