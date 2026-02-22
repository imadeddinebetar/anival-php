<?php

namespace Core\Http\Contracts;

interface CookieJarInterface
{
    public function queue(string $name, string $value, int $minutes, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null, ?string $sameSite = null): void;
    public function expire(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httpOnly = null): void;
    public function getQueuedCookies(): array;
}
