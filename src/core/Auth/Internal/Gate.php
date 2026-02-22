<?php

namespace Core\Auth\Internal;

use Closure;
use Exception;
use Core\Auth\Contracts\GateInterface;
use Core\Auth\Contracts\AuthManagerInterface;

/**
 * @internal
 */
class Gate implements GateInterface
{
    /** @var array<string, Closure> */
    protected array $policies = [];

    /** @var array<string, mixed>|null */
    protected ?array $user;

    protected ?AuthManagerInterface $auth;

    public function __construct(?array $user = null, ?AuthManagerInterface $auth = null)
    {
        $this->user = $user;
        $this->auth = $auth;
    }

    /**
     * Define a new policy.
     */
    public function define(string $ability, Closure $callback): void
    {
        $this->policies[$ability] = $callback;
    }

    /**
     * Resolve the current user.
     */
    protected function resolveUser(): ?array
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->auth) {
            return $this->auth->user();
        }

        // Fallback: resolve from container if available
        if (function_exists('container')) {
            try {
                $auth = container()->get(AuthManagerInterface::class);
                return $auth->user();
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Determine if the given ability should be granted for the current user.
     */
    public function allows(string $ability, ...$args): bool
    {
        $user = $this->resolveUser();

        if (!$user) {
            return false;
        }

        if (!isset($this->policies[$ability])) {
            return false;
        }

        $callback = $this->policies[$ability];

        return (bool) $callback($user, ...$args);
    }

    /**
     * Determine if the given ability should be denied for the current user.
     */
    public function denies(string $ability, ...$args): bool
    {
        return !$this->allows($ability, ...$args);
    }

    /**
     * Determine if the current user has any of the given abilities.
     */
    public function any(array $abilities, ...$args): bool
    {
        foreach ($abilities as $ability) {
            if ($this->allows($ability, ...$args)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the current user has all of the given abilities.
     */
    public function check(array $abilities, ...$args): bool
    {
        foreach ($abilities as $ability) {
            if (!$this->allows($ability, ...$args)) {
                return false;
            }
        }

        return true;
    }
}
