<?php

namespace Core\Events\Internal;

use Core\Events\Contracts\EventDispatcherInterface;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * @internal
 */
class IlluminateEventDispatcher implements EventDispatcherInterface
{
    protected Dispatcher $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function dispatch(string $event, mixed $payload = null): mixed
    {
        return $this->dispatcher->dispatch($event, $payload);
    }

    public function listen(string $event, callable|string|array $listener): void
    {
        $this->dispatcher->listen($event, $listener);
    }

    public function subscribe(string|object $subscriber): void
    {
        $this->dispatcher->subscribe($subscriber);
    }

    public function forget(string $event): void
    {
        $this->dispatcher->forget($event);
    }
}
