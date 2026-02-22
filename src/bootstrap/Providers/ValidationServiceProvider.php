<?php

namespace Bootstrap\Providers;

use Core\Validation\Internal\Validator;

class ValidationServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(\Core\Validation\Contracts\ValidatorFactoryInterface::class, \Core\Validation\Internal\ValidatorFactory::class);
        $this->app->bind('validator', \Core\Validation\Contracts\ValidatorFactoryInterface::class);
    }
}
