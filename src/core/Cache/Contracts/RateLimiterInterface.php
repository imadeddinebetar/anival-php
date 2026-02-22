<?php

namespace Core\Cache\Contracts;

interface RateLimiterInterface
{
    /**
     * Determine if the given key has been "attacked" too many times.
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool;

    /**
     * Increment the counter for a given key for a given decay time.
     */
    public function hit(string $key, int $decaySeconds = 60): int;

    /**
     * Reset the number of attempts for the given key.
     */
    public function resetAttempts(string $key): void;

    /**
     * Get the number of seconds until the "timer" expires.
     */
    public function availableIn(string $key): int;

    /**
     * Get the number of remaining attempts allowed for the key.
     */
    public function remainingAttempts(string $key, int $maxAttempts): int;
}
