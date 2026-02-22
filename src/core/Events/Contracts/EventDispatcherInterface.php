<?php

namespace Core\Events\Contracts;

interface EventDispatcherInterface
{
    public function dispatch(string $event, mixed $payload = null): mixed;
    public function listen(string $event, callable|string|array $listener): void;
    public function subscribe(string|object $subscriber): void;
    public function forget(string $event): void;
}
