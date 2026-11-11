<?php

declare(strict_types=1);

namespace Meridian\Modules\Documents;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Meridian\Modules\Documents\Domain\Repositories\DocumentRepository;
use Meridian\Modules\Documents\Domain\Services\DocumentStorage;
use Meridian\Modules\Documents\Domain\Services\EmployeeLookup;
use Meridian\Modules\Documents\Infrastructure\Employee\EmployeeDirectoryLookup;
use Meridian\Modules\Documents\Infrastructure\Persistence\Repositories\EloquentDocumentRepository;
use Meridian\Modules\Documents\Infrastructure\Storage\FilesystemDocumentStorage;

/**
 * Documents module: files attached to employees (contracts, ID proofs,
 * certificates).
 */
final class DocumentsServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        DocumentRepository::class => EloquentDocumentRepository::class,
        EmployeeLookup::class => EmployeeDirectoryLookup::class,
    ];

    public function register(): void
    {
        $this->app->singleton(DocumentStorage::class, static function (Application $app): DocumentStorage {
            $config = $app->make(Config::class);

            return new FilesystemDocumentStorage(
                disk: $app->make(FilesystemFactory::class)->disk((string) $config->get('documents.disk')),
                directory: (string) $config->get('documents.directory'),
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
