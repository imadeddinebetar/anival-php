<?php

namespace Core\Auth\Internal;

use Core\Cache\Contracts\CacheInterface;
use Core\Auth\Contracts\LoginThrottlerInterface;

/**
 * @internal
 */
class LoginThrottler implements LoginThrottlerInterface
{
    protected CacheInterface $cache;
    protected int $maxAttempts = 5;
    protected int $decayMinutes = 1;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Determine if the given login attempt has too many failed attempts.
     */
    public function hasTooManyAttempts(string $email, string $ip): bool
    {
        return $this->cache->has($this->throttleKey($email, $ip));
    }

    /**
     * Increment the counter for the given login attempt.
     */
    public function incrementAttempts(string $email, string $ip): void
    {
        $key = $this->throttleKey($email, $ip) . ':count';
        $count = (int) $this->cache->get($key, 0) + 1;

        $this->cache->set($key, $count, $this->decayMinutes * 60);

        if ($count >= $this->maxAttempts) {
            $this->cache->set($this->throttleKey($email, $ip), true, $this->decayMinutes * 60);
        }
    }

    /**
     * Clear the login attempts for the given login attempt.
     */
    public function clearAttempts(string $email, string $ip): void
    {
        $this->cache->forget($this->throttleKey($email, $ip));
        $this->cache->forget($this->throttleKey($email, $ip) . ':count');
    }

    /**
     * Get the throttling key for the given login attempt.
     */
    protected function throttleKey(string $email, string $ip): string
    {
        return 'login_throttle:' . md5($email . '|' . $ip);
    }

    /**
     * Get the number of seconds until the next attempt is allowed.
     */
    public function secondsUntilAvailable(string $email, string $ip): int
    {
        // This is a bit simplified as our CacheManager might not return TTL easily
        // depending on the driver. For now, we return decayMinutes as a fallback.
        return $this->decayMinutes * 60;
    }
}
