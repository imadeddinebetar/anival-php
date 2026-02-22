<?php

namespace Core\Auth\Contracts;

interface LoginThrottlerInterface
{
    /**
     * Determine if too many login attempts have been made.
     */
    public function hasTooManyAttempts(string $email, string $ip): bool;

    /**
     * Increment the login attempt counter.
     */
    public function incrementAttempts(string $email, string $ip): void;

    /**
     * Clear the login attempts.
     */
    public function clearAttempts(string $email, string $ip): void;

    /**
     * Get the number of seconds until the next attempt is allowed.
     */
    public function secondsUntilAvailable(string $email, string $ip): int;
}
