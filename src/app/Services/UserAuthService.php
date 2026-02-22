<?php

namespace App\Services;

use Core\Auth\Contracts\AuthManagerInterface;

class UserAuthService
{
    public function __construct(private AuthManagerInterface $auth) {}

    /**
     * Attempt to authenticate the user with the given credentials.
     */
    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        return $this->auth->attempt($email, $password, $remember);
    }

    /**
     * Log the current user out.
     */
    public function logout(): void
    {
        $this->auth->logout();
    }

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return $this->auth->check();
    }

    /**
     * Get the currently authenticated user as an array.
     *
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        return $this->auth->user();
    }

    /**
     * Get the ID of the currently authenticated user.
     */
    public function id(): ?int
    {
        return $this->auth->id();
    }
}
