<?php

namespace Core\Auth\Contracts;

interface TokenManagerInterface
{
    /**
     * Create a new personal access token.
     *
     * @param int $userId
     * @param string $name
     * @param array<string> $abilities
     * @param int|null $expiresInSeconds
     * @return string The plain-text token
     */
    public function createToken(int $userId, string $name, array $abilities = ['*'], ?int $expiresInSeconds = null): string;

    /**
     * Find a token record by its plain-text value.
     */
    public function findToken(string $token): ?object;

    /**
     * Refresh an existing token, revoking the old one.
     */
    public function refreshToken(string $token, ?int $expiresInSeconds = null): ?string;

    /**
     * Revoke a single token.
     */
    public function revokeToken(string $token): bool;

    /**
     * Revoke all tokens for a given user.
     */
    public function revokeAllTokens(int $userId): int;
}
