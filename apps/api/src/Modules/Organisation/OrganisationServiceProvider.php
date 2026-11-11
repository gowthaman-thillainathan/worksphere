<?php

declare(strict_types=1);

namespace Meridian\Modules\Organisation;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Meridian\Modules\Organisation\Application\Actions\DeleteDepartment;
use Meridian\Modules\Organisation\Contracts\DepartmentDirectory;
use Meridian\Modules\Organisation\Contracts\DepartmentReferenceChecker;
use Meridian\Modules\Organisation\Domain\Repositories\DepartmentRepository;
use Meridian\Modules\Organisation\Infrastructure\Persistence\Repositories\EloquentDepartmentRepository;

/**
 * Organisation module: departments (positions, grades and locations follow).
 */
final class OrganisationServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        DepartmentRepository::class => EloquentDepartmentRepository::class,
        DepartmentDirectory::class => EloquentDepartmentRepository::class,
    ];

    public function register(): void
    {
        $this->app->when(DeleteDepartment::class)
            ->needs('$referenceCheckers')
            ->give(static fn (Application $app): iterable => $app->tagged(DepartmentReferenceChecker::TAG));
    }

    public function boot(Config $config): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (! $this->app->routesAreCached()) {
            Route::middleware('api')
                ->prefix((string) $config->get('api.prefix'))
                ->group(__DIR__.'/Http/routes.php');
        }
    }
}
