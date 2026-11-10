<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers every enabled module listed in config/modules.php.
 */
final class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /** @var list<class-string<ServiceProvider>> $providers */
        $providers = $this->app['config']->get('modules.providers', []);

        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
    }
}
