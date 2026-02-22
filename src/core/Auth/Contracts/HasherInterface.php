<?php

namespace Core\Auth\Contracts;

interface HasherInterface
{
    /**
     * Hash the given value.
     */
    public function make(#[\SensitiveParameter] $value, array $options = []): string;

    /**
     * Check the given plain value against a hash.
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool;

    /**
     * Check if the given hash has been hashed using the given options.
     */
    public function needsRehash($hashedValue, array $options = []): bool;

    /**
     * Get information about the given hashed value.
     */
    public function info($hashedValue): array;
}
