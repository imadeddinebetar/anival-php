<?php

namespace Bootstrap\Providers;

use Core\Events\Contracts\EventDispatcherInterface;
use Core\Events\Internal\IlluminateEventDispatcher;

class EventServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton('events', function () {
            return new \Illuminate\Events\Dispatcher();
        });

        $this->app->singleton(EventDispatcherInterface::class, function () {
            $illuminateDispatcher = $this->app->get('events');
            return new IlluminateEventDispatcher($illuminateDispatcher);
        });

        // Keep backward-compat alias for Illuminate consumers (e.g. Eloquent internals)
        $this->app->bind(\Illuminate\Contracts\Events\Dispatcher::class, 'events');
    }
}
