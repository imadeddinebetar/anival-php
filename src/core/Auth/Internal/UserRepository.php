<?php

namespace Core\Auth\Internal;

use Core\Auth\Contracts\UserRepositoryInterface;
use Core\Database\Contracts\DatabaseManagerInterface;

/**
 * @internal
 */
class UserRepository implements UserRepositoryInterface
{
    protected string $table = 'users';

    public function __construct(
        protected DatabaseManagerInterface $db
    ) {}

    public function findByEmail(string $email): ?array
    {
        $result = $this->db->table($this->table)
            ->where('email', $email)
            ->first();

        return $result ? (array) $result : null;
    }

    public function findById(int|string $id): ?array
    {
        $result = $this->db->table($this->table)
            ->where('id', $id)
            ->first();

        return $result ? (array) $result : null;
    }

    public function updateRememberToken(int|string $id, ?string $token): void
    {
        $this->db->table($this->table)
            ->where('id', $id)
            ->update(['remember_token' => $token]);
    }
}
