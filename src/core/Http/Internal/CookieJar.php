<?php

namespace Core\Http\Internal;

use Core\Http\Contracts\CookieJarInterface;

/**
 * @internal
 */
class CookieJar implements CookieJarInterface
{
    /** @var array */
    protected array $queued = [];

    public function queue(string $name, string $value, int $minutes, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?string $sameSite = null): void
    {
        $this->queued[] = [
            'name' => $name,
            'value' => $value,
            'minutes' => $minutes,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
        ];
    }

    public function getQueuedCookies(): array
    {
        return $this->queued;
    }

    public function expire(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null): void
    {
        $this->queue($name, '', -2628000, $path, $domain, $secure, $httpOnly);
    }
}
