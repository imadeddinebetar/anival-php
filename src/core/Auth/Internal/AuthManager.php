<?php

namespace Core\Auth\Internal;

use Core\Auth\Contracts\AuthManagerInterface;

/**
 * @internal
 */
class AuthManager implements AuthManagerInterface
{
    /** @var array<string, mixed>|null */
    protected ?array $user = null;

    public function __construct(
        protected \Core\Session\Contracts\SessionInterface $session,
        protected \Core\Auth\Contracts\UserRepositoryInterface $users,
        protected LoginThrottler $throttler,
        protected \Core\Container\Contracts\ContainerInterface $container,
        protected \Core\Auth\Contracts\RememberMeCookieManagerInterface $cookies,
        protected ?\Core\Events\Contracts\EventDispatcherInterface $events = null
    ) {}

    protected function ensureSessionStarted(): void
    {
        $this->session->start();
    }

    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        $ip = getClientIp();

        if ($this->throttler->hasTooManyAttempts($email, $ip)) {
            return false;
        }

        $user = $this->users->findByEmail($email);

        if (!$user) {
            $this->throttler->incrementAttempts($email, $ip);
            return false;
        }

        if (password_verify($password, $user['password'])) {
            $this->login($user, $remember);
            $this->throttler->clearAttempts($email, $ip);
            return true;
        }

        $this->throttler->incrementAttempts($email, $ip);
        return false;
    }

    /** @param array<string, mixed> $user */
    public function login(array $user, bool $remember = false): void
    {
        $this->ensureSessionStarted();
        $this->session->regenerate(true);

        if (isset($user['password'])) {
            unset($user['password']);
        }
        $this->user = $user;
        $this->session->set('user_id', $user['id']);

        if ($remember) {
            $this->ensureRememberTokenIsSet((int) $user['id']);
        }

        if ($this->events) {
            $this->events->dispatch('auth.login', [$user]);
        }
    }

    public function logout(): void
    {
        $this->ensureSessionStarted();

        $userId = $this->id();
        $user = $this->user();

        if ($userId) {
            $this->users->updateRememberToken($userId, null);
        }

        $this->user = null;
        $this->session->remove('user_id');

        $this->cookies->expireCookie();

        if ($this->events && $user) {
            $this->events->dispatch('auth.logout', [$user]);
        }
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        if ($this->user) {
            return $this->user;
        }

        $this->ensureSessionStarted();

        $userId = $this->session->get('user_id');

        if (!$userId) {
            $userId = $this->attemptLoginViaRememberCookie();
        }

        if ($userId) {
            $user = $this->users->findById($userId);
            if ($user) {
                if (isset($user['password'])) {
                    unset($user['password']);
                }
                $this->user = $user;
            } else {
                return null;
            }
        }

        return $this->user;
    }

    public function id(): int|string|null
    {
        $user = $this->user();
        return $user ? $user['id'] : null;
    }

    public function guest(): bool
    {
        return !$this->check();
    }

    protected function ensureRememberTokenIsSet(int|string $userId): void
    {
        $user = $this->users->findById($userId);
        $token = $user['remember_token'] ?? null;

        if (!$token) {
            $token = bin2hex(random_bytes(45));
            $this->users->updateRememberToken($userId, $token);
        }

        $this->cookies->queueCookie($userId, $token);
    }

    protected function attemptLoginViaRememberCookie(): ?int
    {
        /** @var \Psr\Http\Message\ServerRequestInterface $request */
        $request = $this->container->get(\Psr\Http\Message\ServerRequestInterface::class);

        $payload = $this->cookies->getRecalledUserIdFromCookie($request);

        if (!$payload) {
            return null;
        }

        $userId = $payload['id'];
        $token = $payload['token'];

        $user = $this->users->findById($userId);

        if ($user && isset($user['remember_token']) && hash_equals($user['remember_token'], $token)) {
            $this->session->set('user_id', $user['id']);
            return (int) $user['id'];
        }

        return null;
    }
}
