<?php

namespace Core\Config\Internal;

use Core\Config\Contracts\ConfigRepositoryInterface;
use Core\Container\Internal\Application;

/**
 * @internal
 */
class ConfigRepository implements ConfigRepositoryInterface
{
    /** @var array<string, mixed> */
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->items, $key, $default);
    }

    public function has(string $key): bool
    {
        return data_get($this->items, $key) !== null;
    }

    public function set(string $key, mixed $value): void
    {
        data_set($this->items, $key, $value);
    }

    public function all(): array
    {
        return $this->items;
    }
}
