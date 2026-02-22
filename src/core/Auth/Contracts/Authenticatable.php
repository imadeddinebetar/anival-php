<?php

namespace Core\Auth\Contracts;

/**
 * Contract for models that can be authenticated.
 * Implement this on your User model for type-safe auth.
 */
interface Authenticatable
{
    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier(): int|string;

    /**
     * Get the name of the unique identifier column.
     */
    public function getAuthIdentifierName(): string;

    /**
     * Get the password for the user.
     */
    public function getAuthPassword(): string;

    /**
     * Get the remember token value.
     */
    public function getRememberToken(): ?string;

    /**
     * Set the remember token value.
     */
    public function setRememberToken(string $value): void;

    /**
     * Get the column name for the remember token.
     */
    public function getRememberTokenName(): string;
}
