<?php

namespace Core\Cache\Internal;

use Core\Cache\Contracts\RateLimiterInterface;
use Core\Cache\Contracts\CacheInterface;

/**
 * @internal
 */
class RateLimiter implements RateLimiterInterface
{
    protected CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if the given key has been "attacked" too many times.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        if ($this->cache->get($key . '.attempts', 0) >= $maxAttempts) {
            if ($this->cache->has($key . '.timer')) {
                return true;
            }

            $this->resetAttempts($key);
        }

        return false;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        $attempts = $this->cache->get($key . '.attempts', 0) + 1;
        $this->cache->set($key . '.attempts', $attempts, $decaySeconds);

        if (!$this->cache->has($key . '.timer')) {
            $this->cache->set($key . '.timer', time() + $decaySeconds, $decaySeconds);
        }

        return $attempts;
    }

    /**
     * Reset the number of attempts for the given key.
     */
    public function resetAttempts(string $key): void
    {
        $this->cache->forget($key . '.attempts');
        $this->cache->forget($key . '.timer');
    }

    /**
     * Get the number of seconds until the "timer" expires.
     */
    public function availableIn(string $key): int
    {
        $timer = $this->cache->get($key . '.timer');
        if (!$timer) {
            return 0;
        }

        return max(0, $timer - time());
    }

    /**
     * Get the number of remaining attempts allowed for the key.
     */
    public function remainingAttempts(string $key, int $maxAttempts): int
    {
        $attempts = $this->cache->get($key . '.attempts', 0);
        return max(0, $maxAttempts - $attempts);
    }
}
