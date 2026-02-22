<?php

namespace Core\Auth\Internal;

/**
 * @internal
 */
class TwoFactorAuth
{
    /**
     * Generate a new 16-character Base32 secret.
     */
    public function generateSecret(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $characters[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Get the TOTP code for a given secret.
     */
    public function getCode(string $secret, ?int $time = null): string
    {
        $time = $time ?? time();
        $timestamp = (int) floor($time / 30);
        $secretKey = $this->base32Decode($secret);

        // Pack time into binary string (64-bit big-endian)
        $timeBytes = pack('J', $timestamp);

        $hash = hash_hmac('sha1', $timeBytes, $secretKey, true);

        $offset = ord($hash[19]) & 0xf;
        $otp = (
            (ord($hash[$offset]) & 0x7f) << 24 |
            (ord($hash[$offset + 1]) & 0xff) << 16 |
            (ord($hash[$offset + 2]) & 0xff) << 8 |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a TOTP code.
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $currentTime = time();
        for ($i = -$window; $i <= $window; $i++) {
            $time = $currentTime + ($i * 30);
            if ($this->getCode($secret, $time) === $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * Decode a Base32 string into binary data.
     */
    protected function base32Decode(string $base32): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper($base32);
        $binary = '';

        foreach (str_split($base32) as $char) {
            $val = strpos($characters, $char);
            if ($val === false) {
                continue;
            }
            $binary .= str_pad(decbin($val), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($binary, 8);
        $output = '';
        foreach ($bytes as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $output .= chr((int) bindec($byte));
        }

        return $output;
    }
}
