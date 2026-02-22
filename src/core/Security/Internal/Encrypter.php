<?php

namespace Core\Security\Internal;

use Core\Security\Contracts\EncrypterInterface;

/**
 * @internal
 */
class Encrypter implements EncrypterInterface
{
    protected string $key;
    protected string $cipher = 'aes-256-cbc';

    public function __construct(string $key)
    {
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        if (strlen($key) !== 32) {
            throw new \RuntimeException(
                'Invalid encryption key length. The key must be exactly 32 bytes for AES-256-CBC.'
            );
        }

        $this->key = $key;
    }

    /**
     * Encrypt the given value.
     */
    public function encrypt(string $value): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
        $encrypted = openssl_encrypt($value, $this->cipher, $this->key, 0, $iv);

        if ($encrypted === false) {
            throw new \RuntimeException('Could not encrypt the data.'); // @codeCoverageIgnore
        }

        $iv = base64_encode($iv);
        $mac = hash_hmac('sha256', $iv . $encrypted, $this->key);

        return base64_encode(json_encode([
            'iv' => $iv,
            'value' => $encrypted,
            'mac' => $mac,
        ]) ?: '');
    }

    /**
     * Decrypt the given value.
     */
    public function decrypt(string $payload): string
    {
        $payload = json_decode(base64_decode($payload), true);

        if (!$this->validPayload($payload)) {
            throw new \RuntimeException('The payload is invalid.');
        }

        $iv = base64_decode($payload['iv']);

        if (!hash_equals($payload['mac'], hash_hmac('sha256', $payload['iv'] . $payload['value'], $this->key))) {
            throw new \RuntimeException('The MAC is invalid.');
        }

        $decrypted = openssl_decrypt($payload['value'], $this->cipher, $this->key, 0, $iv);

        if ($decrypted === false) {
            throw new \RuntimeException('Could not decrypt the data.'); // @codeCoverageIgnore
        }

        return $decrypted;
    }

    /**
     * Verify that the encryption payload is valid.
     */
    protected function validPayload(mixed $payload): bool
    {
        return is_array($payload) && isset($payload['iv'], $payload['value'], $payload['mac']) &&
            strlen(base64_decode($payload['iv'], true)) === openssl_cipher_iv_length($this->cipher);
    }
}
