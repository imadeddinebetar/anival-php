<?php

namespace Core\Cache\Internal;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Psr\Cache\CacheItemPoolInterface;
use Core\Cache\Contracts\CacheInterface;

/**
 * @internal
 */
class CacheManager implements CacheInterface
{
    /** @var CacheItemPoolInterface&TagAwareAdapterInterface */
    protected CacheItemPoolInterface $cache;
    /** @var \Redis|\Predis\Client|null */
    protected \Redis|null $redisClient = null;

    /** @param array<string, mixed> $config */
    public function __construct(string $driver = 'file', array $config = [])
    {
        $adapter = $this->createDriver($driver, $config);

        // Wrap with TagAwareAdapter if not already tag aware
        if (!$adapter instanceof TagAwareAdapterInterface) {
            $this->cache = new TagAwareAdapter($adapter);
        } else {
            $this->cache = $adapter; // @codeCoverageIgnore
        }
    }

    /** @param array<string, mixed> $config */
    protected function createDriver(string $driver, array $config): CacheItemPoolInterface
    {
        return match ($driver) {
            'file' => new FilesystemAdapter('app', 0, $config['path'] ?? sys_get_temp_dir()),
            'redis' => $this->createRedisDriver($config), // @codeCoverageIgnore
            'apcu' => new ApcuAdapter('app'), // @codeCoverageIgnore
            default => throw new \InvalidArgumentException("Unsupported cache driver: {$driver}") // @codeCoverageIgnore
        };
    }

    /**
     * @codeCoverageIgnore Requires live Redis server
     */
    protected function createRedisDriver(array $config): CacheItemPoolInterface
    {
        $connection = RedisAdapter::createConnection($config['connection'] ?? 'redis://localhost');
        $this->redisClient = $connection;
        return new RedisAdapter($connection);
    }

    public function tags(array $names): TaggedCache
    {
        return new TaggedCache($this->cache, $names);
    }

    /**
     * Get a lock instance.
     * Note: This returns true if lock acquired, false otherwise.
     * Real implementation of Lock helper object is possible but for now atomic boolean check.
     */
    public function lock(string $key, int $seconds, ?string $owner = null): bool
    {
        $owner = $owner ?? uniqid('', true);
        $lockKey = $this->sanitizeKey('lock:' . $key);

        if ($this->redisClient) { // @codeCoverageIgnoreStart
            /** @var \Redis|\RedisArray|\Predis\Client $client */
            $client = $this->redisClient;

            if ($client instanceof \Redis) {
                return (bool) $client->set($lockKey, $owner, ['NX', 'EX' => $seconds]);
            } elseif (method_exists($client, 'set')) {
                $result = $client->set($lockKey, $owner, 'EX', $seconds, 'NX');
                return $result === true || $result === 'OK';
            }
        } // @codeCoverageIgnoreEnd

        // Fallback for non-Redis (File/APCu) - NOT ATOMIC
        if ($this->has($lockKey)) {
            return false;
        }

        $this->set($lockKey, $owner, $seconds);
        return true;
    }

    public function releaseLock(string $key, string $owner): bool
    {
        $lockKey = $this->sanitizeKey('lock:' . $key);

        if ($this->redisClient) { // @codeCoverageIgnoreStart
            $script = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";
            /** @var \Redis|\Predis\Client $client */
            $client = $this->redisClient;

            if ($client instanceof \Redis) {
                return (bool) $client->eval($script, [$lockKey, $owner], 1);
            }
        } // @codeCoverageIgnoreEnd

        $current = $this->get($lockKey);
        if ($current === $owner) {
            return $this->delete($lockKey);
        }
        return false;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->cache->getItem($this->sanitizeKey($key));
        return $item->isHit() ? $item->get() : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $item = $this->cache->getItem($this->sanitizeKey($key));
        $item->set($value);
        $item->expiresAfter($ttl ?? 3600);
        return $this->cache->save($item);
    }

    public function delete(string $key): bool
    {
        return $this->cache->deleteItem($this->sanitizeKey($key));
    }

    public function clear(): bool
    {
        return $this->cache->clear();
    }

    public function has(string $key): bool
    {
        return $this->cache->hasItem($this->sanitizeKey($key));
    }

    public function forget(string $key): bool
    {
        return $this->delete($key);
    }

    public function flush(): bool
    {
        return $this->clear();
    }

    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->delete($key);
        return $value;
    }

    public function add(string $key, mixed $value, int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }
        return $this->set($key, $value, $ttl ?? 3600);
    }

    public function remember(string $key, int $ttl, \Closure $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function rememberForever(string $key, \Closure $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        $this->set($key, $value, 31536000); // 1 year
        return $value;
    }

    /**
     * Sanitize the cache key to remove reserved characters.
     */
    protected function sanitizeKey(string $key): string
    {
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '.', $key);
    }
}
