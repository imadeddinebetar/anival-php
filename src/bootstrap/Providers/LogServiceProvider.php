<?php

namespace Bootstrap\Providers;

use Psr\Log\LoggerInterface;
use Core\Log\Internal\LogManager;

class LogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('log', function ($c) {
            return new \Core\Log\Internal\LogManager(\Core\Container\Internal\Application::getInstance());
        });

        $this->app->singleton(\Core\Log\Internal\LogManager::class, function ($c) {
            return $c->get('log');
        });

        $this->app->singleton(\Core\Log\Contracts\LogManagerInterface::class, function ($c) {
            return $c->get('log');
        });

        $this->app->singleton(\Psr\Log\LoggerInterface::class, function ($c) {
            return $c->get('log')->driver();
        });

        $this->app->bind('logger', \Core\Log\Contracts\LogManagerInterface::class);
    }
}
