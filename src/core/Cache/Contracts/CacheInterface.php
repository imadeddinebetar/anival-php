<?php

namespace Core\Cache\Contracts;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, int $ttl = null): bool;
    public function has(string $key): bool;
    public function forget(string $key): bool;
    /** @param array<int, string> $names */
    public function tags(array $names): mixed;
    public function flush(): bool;
    /**
     * Get and delete an item from the cache.
     */
    public function pull(string $key, mixed $default = null): mixed;
    /**
     * Store an item in the cache if the key does not exist.
     */
    public function add(string $key, mixed $value, int $ttl = null): bool;
    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     */
    public function remember(string $key, int $ttl, \Closure $callback): mixed;
    /**
     * Get an item from the cache, or execute the given Closure and store the result forever.
     */
    public function rememberForever(string $key, \Closure $callback): mixed;
}
