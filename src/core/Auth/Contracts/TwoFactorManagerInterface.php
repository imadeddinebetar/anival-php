<?php

namespace Core\Auth\Contracts;

interface TwoFactorManagerInterface
{
    /**
     * Enable two-factor authentication for a user.
     *
     * @return string The TOTP secret
     */
    public function enableTwoFactorAuthentication(int $userId): string;

    /**
     * Confirm two-factor authentication with a TOTP code.
     */
    public function confirmTwoFactorAuthentication(int $userId, string $code): bool;
}
