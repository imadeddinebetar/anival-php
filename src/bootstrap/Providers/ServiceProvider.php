<?php

namespace Bootstrap\Providers;

use Core\Container\Internal\Application;
use Core\Container\Contracts\ServiceProviderInterface;

abstract class ServiceProvider implements ServiceProviderInterface
{
    public function __construct(protected Application $app) {}

    abstract public function register(): void;

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        //
    }
}
