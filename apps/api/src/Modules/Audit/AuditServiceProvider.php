<?php

declare(strict_types=1);

namespace Meridian\Modules\Audit;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Meridian\Modules\Audit\Application\Actions\RecordAuditEvent;
use Meridian\Modules\Audit\Contracts\ActorDirectory;
use Meridian\Modules\Audit\Contracts\AuditRecorder;
use Meridian\Modules\Audit\Domain\Repositories\AuditEventRepository;
use Meridian\Modules\Audit\Domain\Services\AuditContext;
use Meridian\Modules\Audit\Infrastructure\Actors\AnonymousActorDirectory;
use Meridian\Modules\Audit\Infrastructure\Console\VerifyAuditChainCommand;
use Meridian\Modules\Audit\Infrastructure\Context\RequestAuditContext;
use Meridian\Modules\Audit\Infrastructure\Persistence\Repositories\EloquentAuditEventRepository;

/**
 * Audit module: tamper-evident business audit trail (docs/09 §6).
 */
final class AuditServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AuditRecorder::class => RecordAuditEvent::class,
        AuditEventRepository::class => EloquentAuditEventRepository::class,
        AuditContext::class => RequestAuditContext::class,
    ];

    public function register(): void
    {
        // Identity provides real names; keep its binding when it registered first.
        $this->app->bindIf(ActorDirectory::class, AnonymousActorDirectory::class);
    }

    public function boot(Config $config): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');

        if (! $this->app->routesAreCached()) {
            Route::middleware('api')
                ->prefix((string) $config->get('api.prefix'))
                ->group(__DIR__.'/Http/routes.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([VerifyAuditChainCommand::class]);
        }
    }
}
