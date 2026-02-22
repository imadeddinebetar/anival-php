<?php

namespace Bootstrap\Providers;

use Core\Console\Contracts\ConsoleKernelInterface;
use Core\Console\Internal\ConsoleKernel;
use Core\Database\Internal\MigrationRepository;

class ConsoleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MigrationRepository::class, function ($app) {
            return new MigrationRepository($app->get('db'));
        });

        $this->app->singleton(ConsoleKernel::class, function ($app) {
            return new ConsoleKernel($app->get(\Core\Container\Internal\Application::class));
        });

        $this->app->singleton(ConsoleKernelInterface::class, function ($app) {
            return $app->get(ConsoleKernel::class);
        });
    }
}
