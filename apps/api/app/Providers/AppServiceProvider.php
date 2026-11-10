<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Meridian\Shared\Http\RateLimiterName;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Default budget for API traffic per user (or per IP for guests), docs/05 §6.
     */
    private const API_REQUESTS_PER_MINUTE = 300;

    public function boot(): void
    {
        // Fail fast on lazy loading, mass-assignment and missing attributes outside production.
        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for(
            RateLimiterName::Api->value,
            static fn (Request $request): Limit => Limit::perMinute(self::API_REQUESTS_PER_MINUTE)
                ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())),
        );
    }
}
