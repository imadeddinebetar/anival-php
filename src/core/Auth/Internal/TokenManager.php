<?php

namespace Core\Auth\Internal;

use Core\Auth\Contracts\TokenManagerInterface;

/**
 * @internal
 */
class TokenManager implements TokenManagerInterface
{
    /** @param array<string> $abilities */
    public function createToken(int $userId, string $name, array $abilities = ['*'], ?int $expiresInSeconds = null): string
    {
        $token = bin2hex(random_bytes(32));

        PersonalAccessToken::create([
            'user_id' => $userId,
            'name' => $name,
            'token' => hash('sha256', $token),
            'abilities' => $abilities,
            'expires_at' => $expiresInSeconds ? now()->addSeconds($expiresInSeconds) : null,
        ]);

        return $token;
    }

    public function findToken(string $token): ?PersonalAccessToken
    {
        $hashedToken = hash('sha256', $token);
        return PersonalAccessToken::where('token', $hashedToken)->first();
    }

    public function refreshToken(string $token, ?int $expiresInSeconds = null): ?string
    {
        $accessToken = $this->findToken($token);

        if (!$accessToken) {
            return null;
        }

        $userId = $accessToken->user_id;
        $name = $accessToken->name;
        $abilities = (array) $accessToken->abilities;

        $accessToken->delete();

        return $this->createToken($userId, $name, $abilities, $expiresInSeconds);
    }

    public function revokeToken(string $token): bool
    {
        $accessToken = $this->findToken($token);

        if (!$accessToken) {
            return false;
        }

        return (bool) $accessToken->delete();
    }

    public function revokeAllTokens(int $userId): int
    {
        return PersonalAccessToken::where('user_id', $userId)->delete();
    }
}
