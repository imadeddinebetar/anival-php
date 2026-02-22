<?php

namespace Bootstrap\Providers;

use Core\Debug\Internal\DebugBar;
use Core\Debug\Internal\Collectors\QueryCollector;
use Core\Debug\Internal\Collectors\RouteCollector;

class DebugServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DebugBar::class, function () {
            $debugBar = new DebugBar($this->app);

            if ($debugBar->isEnabled()) {
                $debugBar->addCollector(new QueryCollector());
                $debugBar->addCollector(new RouteCollector());
            }

            return $debugBar;
        });

        $this->app->singleton(QueryCollector::class, function ($app) {
            return $app->get(DebugBar::class)->getCollector(QueryCollector::class);
        });

        $this->app->singleton(RouteCollector::class, function ($app) {
            return $app->get(DebugBar::class)->getCollector(RouteCollector::class);
        });
    }

    public function boot(): void
    {
        //
    }
}
