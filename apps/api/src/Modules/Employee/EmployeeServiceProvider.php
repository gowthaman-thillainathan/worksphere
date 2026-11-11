<?php

declare(strict_types=1);

namespace Meridian\Modules\Employee;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Meridian\Modules\Employee\Application\Data\EmployeeNumberPolicy;
use Meridian\Modules\Employee\Domain\Repositories\EmployeeRepository;
use Meridian\Modules\Employee\Domain\Services\DepartmentLookup;
use Meridian\Modules\Employee\Domain\Services\EmployeePhotoStorage;
use Meridian\Modules\Employee\Infrastructure\Organisation\EmployeeDepartmentReferenceChecker;
use Meridian\Modules\Employee\Infrastructure\Organisation\OrganisationDepartmentLookup;
use Meridian\Modules\Employee\Infrastructure\Persistence\Repositories\EloquentEmployeeRepository;
use Meridian\Modules\Employee\Infrastructure\Storage\FilesystemEmployeePhotoStorage;
use Meridian\Modules\Organisation\Contracts\DepartmentReferenceChecker;

/**
 * Employee module: profiles, directory search, headcount and profile photos.
 */
final class EmployeeServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        EmployeeRepository::class => EloquentEmployeeRepository::class,
        DepartmentLookup::class => OrganisationDepartmentLookup::class,
    ];

    public function register(): void
    {
        // Lets the Organisation module refuse to delete departments that still have employees.
        $this->app->tag([EmployeeDepartmentReferenceChecker::class], DepartmentReferenceChecker::TAG);

        $this->app->singleton(EmployeePhotoStorage::class, static function (Application $app): EmployeePhotoStorage {
            $config = $app->make(Config::class);

            return new FilesystemEmployeePhotoStorage(
                disk: $app->make(FilesystemFactory::class)->disk((string) $config->get('employee.photo.disk')),
                directory: (string) $config->get('employee.photo.directory'),
            );
        });

        $this->app->singleton(EmployeeNumberPolicy::class, static function (Application $app): EmployeeNumberPolicy {
            $config = $app->make(Config::class);

            return new EmployeeNumberPolicy(
                prefix: (string) $config->get('employee.number.prefix'),
                padding: (int) $config->get('employee.number.padding'),
            );
        });
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
