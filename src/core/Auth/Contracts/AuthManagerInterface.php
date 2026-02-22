<?php

namespace Core\Auth\Contracts;

interface AuthManagerInterface
{
    public function attempt(string $email, string $password, bool $remember = false): bool;
    /** @param array<string, mixed> $user */
    public function login(array $user, bool $remember = false): void;
    public function logout(): void;
    public function check(): bool;
    public function guest(): bool;
    public function user(): ?array;
    public function id(): int|string|null;
}
