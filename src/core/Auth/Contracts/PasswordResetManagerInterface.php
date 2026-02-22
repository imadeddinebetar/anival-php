<?php

namespace Core\Auth\Contracts;

interface PasswordResetManagerInterface
{
    /**
     * Create a password reset token for the given email.
     */
    public function createPasswordResetToken(string $email): string;

    /**
     * Validate a password reset token.
     */
    public function validatePasswordResetToken(string $email, string $token): bool;

    /**
     * Reset the password for the given email using a valid token.
     */
    public function resetPassword(string $email, string $token, string $password): bool;
}
