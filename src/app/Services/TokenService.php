<?php

namespace App\Services;

use Core\Auth\Contracts\TokenManagerInterface;

class TokenService
{
    public function __construct(private TokenManagerInterface $tokenManager) {}

    /**
     * Refresh the given token for a user, revoking the old one.
     *
     * @return array<string, mixed>
     */
    public function refresh(string $currentToken, int $userId): array
    {
        $newToken = $this->tokenManager->createToken($userId, 'Refreshed Token');
        $this->tokenManager->revokeToken($currentToken);

        return [
            'token' => $newToken,
            'message' => 'Token refreshed successfully',
        ];
    }

    /**
     * Revoke a single token.
     */
    public function revoke(string $token): void
    {
        $this->tokenManager->revokeToken($token);
    }

    /**
     * Revoke all tokens for a user.
     */
    public function revokeAll(int $userId): void
    {
        $this->tokenManager->revokeAllTokens($userId);
    }
}
