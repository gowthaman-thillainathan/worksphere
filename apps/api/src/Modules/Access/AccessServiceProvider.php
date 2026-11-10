<?php

declare(strict_types=1);

namespace Meridian\Modules\Access;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Meridian\Modules\Access\Application\Actions\AssignRole;
use Meridian\Modules\Access\Contracts\Authorizer;
use Meridian\Modules\Access\Contracts\Permission;
use Meridian\Modules\Access\Contracts\RoleAssigner;
use Meridian\Modules\Access\Domain\Repositories\PermissionRepository;
use Meridian\Modules\Access\Domain\Repositories\RoleRepository;
use Meridian\Modules\Access\Infrastructure\Authorization\CachedRoleBasedAuthorizer;
use Meridian\Modules\Access\Infrastructure\Console\SyncPermissionsCommand;
use Meridian\Modules\Access\Infrastructure\Persistence\Repositories\EloquentPermissionRepository;
use Meridian\Modules\Access\Infrastructure\Persistence\Repositories\EloquentRoleRepository;

/**
 * Access module: roles, permissions and authorisation decisions (docs/07).
 */
final class AccessServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PermissionRepository::class => EloquentPermissionRepository::class,
        RoleRepository::class => EloquentRoleRepository::class,
        RoleAssigner::class => AssignRole::class,
    ];

    public function register(): void
    {
        // Scoped: the per-request memo must not leak between requests in long-running workers.
        $this->app->scoped(Authorizer::class, static fn (Application $app): Authorizer => new CachedRoleBasedAuthorizer(
            permissions: $app->make(PermissionRepository::class),
            cache: $app->make(Cache::class),
            ttlSeconds: (int) $app['config']->get('access.cache_ttl_seconds'),
        ));
    }

    public function boot(Gate $gate): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
        $this->registerGates($gate);
        $this->registerRoutes();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
    }

    /**
     * One gate per permission, so routes and policies can use
     * `can:<permission>` / Gate::authorize(<permission>).
     */
    private function registerGates(Gate $gate): void
    {
        foreach (Permission::cases() as $permission) {
            $gate->define(
                $permission->value,
                fn (Authenticatable $user): bool => $this->app->make(Authorizer::class)
                    ->allows((string) $user->getAuthIdentifier(), $permission),
            );
        }
    }

    private function registerRoutes(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('api')
            ->prefix((string) $this->app['config']->get('api.prefix'))
            ->group(__DIR__.'/Http/routes.php');
    }
}
