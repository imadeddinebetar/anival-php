<?php

namespace Core\Auth\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RememberMeCookieManagerInterface
{
    public function createCookieValue(int|string $userId, string $token): string;
    public function queueCookie(int|string $userId, string $token): void;
    public function expireCookie(): void;
    /** @return array{id: string, token: string}|null */
    public function getRecalledUserIdFromCookie(ServerRequestInterface $request): ?array;
    public function cookieName(): string;
    public function cookieDuration(): int;
}
