<?php

namespace Core\Auth\Internal;

use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Core\Auth\Contracts\HasherInterface;

/**
 * @internal
 */
class Hasher implements HasherContract, HasherInterface
{
    /**
     * Hash the given value.
     *
     * @param  string  $value
     * @param  array  $options
     * @return string
     */
    public function make(#[\SensitiveParameter] $value, array $options = []): string
    {
        return password_hash($value, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Check the given plain value against a hash.
     *
     * @param  string  $value
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function check(#[\SensitiveParameter] $value, $hashedValue, array $options = []): bool
    {
        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param  string  $hashedValue
     * @param  array  $options
     * @return bool
     */
    public function needsRehash($hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Get information about the given hashed value.
     *
     * @param  string  $hashedValue
     * @return array
     */
    public function info($hashedValue): array
    {
        return password_get_info($hashedValue);
    }

    /**
     * Check if the given value is a hashed value.
     *
     * @param  string  $value
     * @return bool
     */
    public function isHashed($value)
    {
        $info = password_get_info($value);

        return $info['algo'] !== 0 && $info['algo'] !== null;
    }

    /**
     * Check if the configuration of the hash driver is accurate.
     *
     * @param  string  $value
     * @return bool
     */
    public function verifyConfiguration($value)
    {
        return true;
    }
}
