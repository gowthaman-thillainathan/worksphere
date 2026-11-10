<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use Meridian\Shared\Domain\Exceptions\DomainException;
use Meridian\Shared\Http\Middleware\AssignRequestId;
use Meridian\Shared\Http\Middleware\ForceJsonResponse;
use Meridian\Shared\Http\Middleware\SecurityHeaders;
use Meridian\Shared\Http\RateLimiterName;
use Meridian\Shared\Http\Responses\ApiExceptionRenderer;

$isApiRequest = static fn (Request $request): bool => $request->is(trim((string) config('api.prefix'), '/').'/*');

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/health/live',
        then: static function (): void {
            // Module routes are registered by each module's service provider.
            Route::group([], base_path('routes/docs.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend([AssignRequestId::class, SecurityHeaders::class]);

        $middleware->api(
            append: [ThrottleRequests::using(RateLimiterName::Api->value)],
            prepend: [ForceJsonResponse::class],
        );

        // Stateless API: no guest redirects, unauthenticated requests get 401.
        $middleware->redirectGuestsTo(static fn (): ?string => null);
    })
    ->withExceptions(function (Exceptions $exceptions) use ($isApiRequest): void {
        // Business-rule failures are expected outcomes, not incidents.
        $exceptions->dontReport(DomainException::class);

        $exceptions->shouldRenderJsonWhen(
            static fn (Request $request): bool => $isApiRequest($request) || $request->expectsJson(),
        );

        $exceptions->render(
            static fn (Throwable $exception, Request $request) => $isApiRequest($request)
                ? app(ApiExceptionRenderer::class)->render($exception, $request)
                : null,
        );
    })->create();
