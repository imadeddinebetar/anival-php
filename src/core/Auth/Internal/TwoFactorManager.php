<?php

namespace Core\Auth\Internal;

use Core\Database\Contracts\DatabaseManagerInterface;
use Core\Auth\Contracts\TwoFactorManagerInterface;

/**
 * @internal
 */
class TwoFactorManager implements TwoFactorManagerInterface
{
    protected DatabaseManagerInterface $db;
    protected TwoFactorAuth $twoFactor;
    protected string $table = 'users';

    public function __construct(DatabaseManagerInterface $db, TwoFactorAuth $twoFactor)
    {
        $this->db = $db;
        $this->twoFactor = $twoFactor;
    }

    public function enableTwoFactorAuthentication(int $userId): string
    {
        $secret = $this->twoFactor->generateSecret();

        $this->db->table($this->table)
            ->where('id', $userId)
            ->update([
                'two_factor_secret' => encrypt($secret),
                'two_factor_recovery_codes' => encrypt(json_encode($this->generateRecoveryCodes()) ?: ''),
            ]);

        return $secret;
    }

    public function confirmTwoFactorAuthentication(int $userId, string $code): bool
    {
        $user = (array) $this->db->table($this->table)->where('id', $userId)->first();
        if (!$user || !$user['two_factor_secret']) {
            return false;
        }

        $secret = decrypt($user['two_factor_secret']);

        if ($this->twoFactor->verifyCode($secret, $code)) {
            $this->db->table($this->table)
                ->where('id', $userId)
                ->update(['two_factor_confirmed_at' => now()->toDateTimeString()]);
            return true;
        }

        return false;
    }

    /** @return array<int, string> */
    protected function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = bin2hex(random_bytes(10));
        }
        return $codes;
    }
}
