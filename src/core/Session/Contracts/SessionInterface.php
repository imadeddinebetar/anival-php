<?php

namespace Core\Session\Contracts;

interface SessionInterface
{
    public function start(): bool;
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function all(): array;
    public function set(string $key, mixed $value): void;
    public function put(string $key, mixed $value): void;
    public function push(string $key, mixed $value): void;
    public function remove(string $key): mixed;
    public function forget(string $key): void;
    public function flush(): void;
    public function regenerate(bool $destroy = false): bool;
    public function flash(string $key, mixed $value = true): void;
    public function destroy(): bool;
}
