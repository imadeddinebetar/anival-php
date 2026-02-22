<?php

namespace Core\Cache\Internal;

use Core\Cache\Contracts\CacheInterface;

/**
 * In-memory array cache driver — data lives only for the current request.
 * Ideal for testing and scenarios where persistence is not needed.
 *
 * @internal
 */
class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires_at: int|null}> */
    protected array $store = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->store[$key]['value'];
    }

    public function set(string $key, mixed $value, int $ttl = null): bool
    {
        $this->store[$key] = [
            'value' => $value,
            'expires_at' => $ttl !== null ? time() + $ttl : null,
        ];

        return true;
    }

    public function has(string $key): bool
    {
        if (!isset($this->store[$key])) {
            return false;
        }

        $item = $this->store[$key];

        if ($item['expires_at'] !== null && $item['expires_at'] <= time()) {
            unset($this->store[$key]);
            return false;
        }

        return true;
    }

    public function forget(string $key): bool
    {
        $exists = isset($this->store[$key]);
        unset($this->store[$key]);
        return $exists;
    }

    public function delete(string $key): bool
    {
        return $this->forget($key);
    }

    /** @param array<int, string> $names */
    public function tags(array $names): mixed
    {
        // Array cache does not support tags — return self for chaining
        return $this;
    }

    public function flush(): bool
    {
        $this->store = [];
        return true;
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    public function add(string $key, mixed $value, int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->set($key, $value, $ttl);
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    public function rememberForever(string $key, \Closure $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();
        $this->set($key, $value);

        return $value;
    }
}
