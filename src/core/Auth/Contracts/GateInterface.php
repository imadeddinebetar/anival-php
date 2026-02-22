<?php

namespace Core\Auth\Contracts;

use Closure;

interface GateInterface
{
    /**
     * Define a new policy.
     */
    public function define(string $ability, Closure $callback): void;

    /**
     * Determine if the given ability should be granted for the current user.
     */
    public function allows(string $ability, ...$args): bool;

    /**
     * Determine if the given ability should be denied for the current user.
     */
    public function denies(string $ability, ...$args): bool;

    /**
     * Determine if the current user has any of the given abilities.
     */
    public function any(array $abilities, ...$args): bool;

    /**
     * Determine if the current user has all of the given abilities.
     */
    public function check(array $abilities, ...$args): bool;
}
