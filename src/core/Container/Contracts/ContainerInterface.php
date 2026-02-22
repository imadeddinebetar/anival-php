<?php

namespace Core\Container\Contracts;

interface ContainerInterface
{
    public function get(string $id): mixed;
    public function has(string $id): bool;
    public function bind(string $id, mixed $concrete = null): void;
    public function singleton(string $id, mixed $concrete = null): void;
    public function make(string $id, array $parameters = []): mixed;
    public function call(callable|array|string $callable, array $parameters = []): mixed;
}
