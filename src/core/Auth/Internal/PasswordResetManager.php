<?php

namespace Core\Auth\Internal;

use Core\Database\Contracts\DatabaseManagerInterface;
use Core\Auth\Contracts\PasswordResetManagerInterface;

/**
 * @internal
 */
class PasswordResetManager implements PasswordResetManagerInterface
{
    protected DatabaseManagerInterface $db;
    protected string $table = 'users';

    public function __construct(DatabaseManagerInterface $db)
    {
        $this->db = $db;
    }

    public function createPasswordResetToken(string $email): string
    {
        $token = bin2hex(random_bytes(32));

        $this->db->table('password_resets')->insert([
            'email' => $email,
            'token' => hash('sha256', $token),
            'created_at' => now()->toDateTimeString(),
        ]);

        return $token;
    }

    public function validatePasswordResetToken(string $email, string $token): bool
    {
        $reset = $this->db->table('password_resets')
            ->where('email', $email)
            ->where('token', hash('sha256', $token))
            ->first();

        if (!$reset) {
            return false;
        }

        $createdAt = \Carbon\Carbon::parse($reset->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            return false;
        }

        return true;
    }

    public function resetPassword(string $email, string $token, string $password): bool
    {
        if (!$this->validatePasswordResetToken($email, $token)) {
            return false;
        }

        $this->db->table($this->table)
            ->where('email', $email)
            ->update(['password' => password_hash($password, PASSWORD_ARGON2ID)]);

        $this->db->table('password_resets')
            ->where('email', $email)
            ->delete();

        return true;
    }
}
