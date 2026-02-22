<?php

namespace Core\Auth\Internal;

use Core\Auth\Contracts\RememberMeCookieManagerInterface;
use Core\Http\Contracts\CookieJarInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
class RememberMeCookieManager implements RememberMeCookieManagerInterface
{
    public function __construct(
        protected CookieJarInterface $cookieJar
    ) {}

    public function queueCookie(int|string $userId, string $token): void
    {
        $value = $this->createCookieValue($userId, $token);

        $this->cookieJar->queue(
            $this->cookieName(),
            $value,
            $this->cookieDuration(),
            config('session.path', '/'),
            config('session.domain', ''),
            config('session.secure', true),
            config('session.httponly', true),
            config('session.samesite', 'Lax')
        );
    }

    public function expireCookie(): void
    {
        $this->cookieJar->queue(
            $this->cookieName(),
            '',
            -60,
            config('session.path', '/'),
            config('session.domain', ''),
            config('session.secure', true),
            config('session.httponly', true),
            config('session.samesite', 'Lax')
        );
    }

    /** @return array{id: string, token: string}|null */
    public function getRecalledUserIdFromCookie(ServerRequestInterface $request): ?array
    {
        $cookies = $request->getCookieParams();
        $value = $cookies[$this->cookieName()] ?? null;

        if (!$value) {
            return null;
        }

        $segments = explode('|', $value);
        if (count($segments) !== 3) {
            return null;
        }

        [$userId, $token, $hash] = $segments;

        if (!hash_equals($hash, hash_hmac('sha256', $userId . '|' . $token, config('app.key', '')))) {
            return null;
        }

        return ['id' => $userId, 'token' => $token];
    }

    public function createCookieValue(int|string $userId, string $token): string
    {
        return $userId . '|' . $token . '|' . hash_hmac('sha256', $userId . '|' . $token, config('app.key', ''));
    }

    public function cookieName(): string
    {
        return 'remember_web';
    }

    public function cookieDuration(): int
    {
        return 60 * 24 * 30; // 30 days
    }
}
