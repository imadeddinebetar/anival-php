<?php

namespace Core\Cache\Internal;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

/**
 * @internal
 */
class TaggedCache
{
    protected TagAwareAdapterInterface $store;
    protected array $tags;

    public function __construct(TagAwareAdapterInterface $store, array $tags)
    {
        $this->store = $store;
        $this->tags = $tags;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $item = $this->store->getItem($this->sanitizeKey($key));
        return $item->isHit() ? $item->get() : $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $item = $this->store->getItem($this->sanitizeKey($key));
        $item->set($value);
        $item->expiresAfter($ttl);
        $item->tag($this->tags);

        return $this->store->save($item);
    }

    public function delete(string $key): bool
    {
        return $this->store->deleteItem($this->sanitizeKey($key));
    }

    public function flush(): bool
    {
        return $this->store->invalidateTags($this->tags);
    }

    /**
     * Sanitize the cache key to remove reserved characters.
     */
    protected function sanitizeKey(string $key): string
    {
        return str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '.', $key);
    }
}
