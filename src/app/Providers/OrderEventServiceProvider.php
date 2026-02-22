<?php

namespace App\Providers;

use Bootstrap\Providers\EventServiceProvider;
use App\Events\OrderShipped;
use App\Listeners\ProcessShippedOrder;

class OrderEventServiceProvider extends EventServiceProvider
{
    /**
     * The event-to-listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected array $listen = [
        OrderShipped::class => [
            ProcessShippedOrder::class,
        ],
    ];

    public function register(): void
    {
        // Base EventServiceProvider registers the events dispatcher
        parent::register();
    }

    public function boot(): void
    {
        $dispatcher = $this->app->get('events');

        foreach ($this->listen as $eventClass => $listeners) {
            foreach ($listeners as $listenerClass) {
                $dispatcher->listen($eventClass, function ($event) use ($listenerClass) {
                    $listener = $this->app->get($listenerClass);
                    $listener->handle($event);
                });
            }
        }
    }
}
