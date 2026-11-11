<?php

declare(strict_types=1);

namespace Meridian\Modules\Identity;

use Carbon\CarbonImmutable;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Meridian\Modules\Audit\Contracts\ActorDirectory;
use Meridian\Modules\Identity\Application\Actions\ProvisionAccount;
use Meridian\Modules\Identity\Application\Data\LockoutPolicy;
use Meridian\Modules\Identity\Application\Data\TokenPolicy;
use Meridian\Modules\Identity\Application\Listeners\LogSecurityEvent;
use Meridian\Modules\Identity\Application\Listeners\NotifyUserOfLockout;
use Meridian\Modules\Identity\Contracts\AccountProvisioner;
use Meridian\Modules\Identity\Domain\Events\SecurityEvent;
use Meridian\Modules\Identity\Domain\Events\UserLockedOut;
use Meridian\Modules\Identity\Domain\Repositories\RefreshTokenRepository;
use Meridian\Modules\Identity\Domain\Repositories\UserRepository;
use Meridian\Modules\Identity\Domain\Services\AccessTokenCodec;
use Meridian\Modules\Identity\Domain\Services\AccessTokenRevocationList;
use Meridian\Modules\Identity\Domain\ValueObjects\EmailVerificationHash;
use Meridian\Modules\Identity\Http\Middleware\EnsureEmailIsVerified;
use Meridian\Modules\Identity\Http\RateLimiterName;
use Meridian\Modules\Identity\Http\RouteName;
use Meridian\Modules\Identity\Infrastructure\Audit\UserActorDirectory;
use Meridian\Modules\Identity\Infrastructure\Console\CreateUserCommand;
use Meridian\Modules\Identity\Infrastructure\Persistence\Models\User;
use Meridian\Modules\Identity\Infrastructure\Persistence\Repositories\EloquentRefreshTokenRepository;
use Meridian\Modules\Identity\Infrastructure\Persistence\Repositories\EloquentUserRepository;
use Meridian\Modules\Identity\Infrastructure\Security\BearerTokenAuthenticator;
use Meridian\Modules\Identity\Infrastructure\Security\CacheAccessTokenRevocationList;
use Meridian\Modules\Identity\Infrastructure\Security\FirebaseJwtAccessTokenCodec;
use Meridian\Modules\Identity\Infrastructure\Support\FrontendUrls;
use Meridian\Shared\Http\MiddlewareGroup;

/**
 * Identity module: accounts, credentials, tokens and sessions (docs/06).
 */
final class IdentityServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        RefreshTokenRepository::class => EloquentRefreshTokenRepository::class,
        AccessTokenRevocationList::class => CacheAccessTokenRevocationList::class,
        AccountProvisioner::class => ProvisionAccount::class,
        ActorDirectory::class => UserActorDirectory::class,
    ];

    public function register(): void
    {
        $this->app->singleton(AccessTokenCodec::class, static function (Application $app): AccessTokenCodec {
            $config = $app->make(Config::class);

            return new FirebaseJwtAccessTokenCodec(
                secret: (string) $config->get('identity.jwt.secret'),
                algorithm: (string) $config->get('identity.jwt.algorithm'),
                keyId: (string) $config->get('identity.jwt.key_id'),
                issuer: (string) $config->get('identity.jwt.issuer'),
                audience: (string) $config->get('identity.jwt.audience'),
                leewaySeconds: (int) $config->get('identity.jwt.leeway_seconds'),
            );
        });

        $this->app->singleton(TokenPolicy::class, static function (Application $app): TokenPolicy {
            $config = $app->make(Config::class);

            return new TokenPolicy(
                accessTokenTtlSeconds: (int) $config->get('identity.jwt.ttl_seconds'),
                refreshTokenTtlSeconds: (int) $config->get('identity.refresh_token.ttl_seconds'),
                refreshTokenEntropyBytes: (int) $config->get('identity.refresh_token.entropy_bytes'),
            );
        });

        $this->app->singleton(LockoutPolicy::class, static function (Application $app): LockoutPolicy {
            $config = $app->make(Config::class);

            return new LockoutPolicy(
                maxAttempts: (int) $config->get('identity.lockout.max_attempts'),
                durationSeconds: (int) $config->get('identity.lockout.duration_seconds'),
            );
        });
    }

    public function boot(Router $router, Dispatcher $events, Config $config): void
    {
        $this->registerGuard();
        $this->registerMiddlewareGroups($router);
        $this->registerRateLimiters($config);
        $this->registerPasswordPolicy($config);
        $this->registerNotificationLinks($config);
        $this->registerListeners($events);
        $this->registerRoutes($config);
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([CreateUserCommand::class]);
        }
    }

    private function registerGuard(): void
    {
        Auth::viaRequest(
            BearerTokenAuthenticator::DRIVER,
            fn (Request $request): ?User => $this->app->make(BearerTokenAuthenticator::class)($request),
        );
    }

    private function registerMiddlewareGroups(Router $router): void
    {
        $router->middlewareGroup(MiddlewareGroup::Authenticated->value, [
            Authenticate::using(BearerTokenAuthenticator::GUARD),
            EnsureEmailIsVerified::class,
        ]);
    }

    private function registerRateLimiters(Config $config): void
    {
        $limit = static fn (string $key): int => (int) $config->get('identity.rate_limits.'.$key);

        RateLimiter::for(RateLimiterName::Login->value, static fn (Request $request): array => [
            Limit::perMinute($limit('login_per_account'))
                ->by(mb_strtolower($request->string('email')->trim()->toString()).'|'.$request->ip()),
            Limit::perMinute($limit('login_per_ip'))->by((string) $request->ip()),
        ]);

        RateLimiter::for(RateLimiterName::PasswordReset->value, static fn (Request $request): Limit => Limit::perMinute($limit('password_reset_per_ip'))->by((string) $request->ip()));

        RateLimiter::for(RateLimiterName::Refresh->value, static fn (Request $request): Limit => Limit::perMinute($limit('refresh_per_ip'))->by((string) $request->ip()));

        RateLimiter::for(RateLimiterName::EmailVerification->value, static fn (Request $request): Limit => Limit::perMinute($limit('verification_per_user'))
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));
    }

    /**
     * NIST 800-63B: length over composition rules, plus a breached-password
     * check (k-anonymity, the password never leaves the server).
     */
    private function registerPasswordPolicy(Config $config): void
    {
        Password::defaults(static function () use ($config): Password {
            $rule = Password::min((int) $config->get('identity.password.min_length'))
                ->max((int) $config->get('identity.password.max_length'));

            return $config->get('identity.password.check_compromised') ? $rule->uncompromised() : $rule;
        });
    }

    /**
     * E-mail links: reset links open the SPA; verification links hit the
     * signed API route, which then redirects to the SPA.
     */
    private function registerNotificationLinks(Config $config): void
    {
        ResetPasswordNotification::createUrlUsing(
            fn (User $user, string $token): string => $this->app->make(FrontendUrls::class)->resetPassword($token, $user->getEmailForPasswordReset()),
        );

        VerifyEmailNotification::createUrlUsing(static fn (User $user): string => URL::temporarySignedRoute(
            RouteName::VerifyEmail->value,
            CarbonImmutable::now()->addMinutes((int) $config->get('identity.email_verification.expire_minutes')),
            ['id' => $user->getKey(), 'hash' => EmailVerificationHash::of($user->getEmailForVerification())],
        ));
    }

    private function registerListeners(Dispatcher $events): void
    {
        $events->listen(SecurityEvent::class, LogSecurityEvent::class);
        $events->listen(UserLockedOut::class, NotifyUserOfLockout::class);
    }

    private function registerRoutes(Config $config): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('api')
            ->prefix((string) $config->get('api.prefix'))
            ->group(__DIR__.'/Http/routes.php');
    }
}
