<?php

namespace Bootstrap\Providers;

use Core\Auth\Internal\AuthManager;
use Core\Auth\Internal\Gate;
use Core\Auth\Internal\TwoFactorAuth;
use Core\Auth\Internal\TokenManager;
use Core\Auth\Internal\PasswordResetManager;
use Core\Auth\Internal\TwoFactorManager;
use Core\Auth\Internal\LoginThrottler;

class AuthServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        // Bind interfaces to implementation
        $this->app->bind(\Core\Auth\Contracts\UserRepositoryInterface::class, \Core\Auth\Internal\UserRepository::class);
        $this->app->bind(\Core\Auth\Contracts\RememberMeCookieManagerInterface::class, \Core\Auth\Internal\RememberMeCookieManager::class);
        $this->app->bind(\Core\Http\Contracts\CookieJarInterface::class, \Core\Http\Internal\CookieJar::class);
        $this->app->singleton(\Core\Http\Contracts\CookieJarInterface::class, \Core\Http\Internal\CookieJar::class);

        // Bind AuthManager
        $this->app->singleton(\Core\Auth\Contracts\AuthManagerInterface::class, AuthManager::class);
        $this->app->singleton(AuthManager::class, AuthManager::class);
        $this->app->bind('auth', \Core\Auth\Contracts\AuthManagerInterface::class);

        // Bind Gate
        $this->app->singleton(\Core\Auth\Contracts\GateInterface::class, Gate::class);
        $this->app->singleton(Gate::class, Gate::class);
        $this->app->bind('gate', \Core\Auth\Contracts\GateInterface::class);

        // Others
        $this->app->singleton(TwoFactorAuth::class, TwoFactorAuth::class);
        $this->app->bind('2fa', TwoFactorAuth::class);

        // Token Manager — bind interface + concrete
        $this->app->singleton(TokenManager::class, TokenManager::class);
        $this->app->bind(\Core\Auth\Contracts\TokenManagerInterface::class, TokenManager::class);

        // Password Reset Manager — bind interface + concrete
        $this->app->singleton(PasswordResetManager::class, PasswordResetManager::class);
        $this->app->bind(\Core\Auth\Contracts\PasswordResetManagerInterface::class, PasswordResetManager::class);

        // Two Factor Manager — bind interface + concrete
        $this->app->singleton(TwoFactorManager::class, TwoFactorManager::class);
        $this->app->bind(\Core\Auth\Contracts\TwoFactorManagerInterface::class, TwoFactorManager::class);

        // Login Throttler — bind interface + concrete
        $this->app->singleton(LoginThrottler::class, LoginThrottler::class);
        $this->app->bind(\Core\Auth\Contracts\LoginThrottlerInterface::class, LoginThrottler::class);

        $this->app->singleton(\Core\Auth\Contracts\HasherInterface::class, \Core\Auth\Internal\Hasher::class);
        $this->app->singleton(\Core\Auth\Internal\Hasher::class, \Core\Auth\Internal\Hasher::class);
        $this->app->bind('hash', \Core\Auth\Contracts\HasherInterface::class);


        // Also bind to Illuminate Container for Eloquent (HashedCast)
        if (class_exists(\Illuminate\Support\Facades\Facade::class)) {
            $container = \Illuminate\Support\Facades\Facade::getFacadeApplication();
            if ($container) {
                $container->bind('hash', function () {
                    return new \Core\Auth\Internal\Hasher();
                });
            }
        }
    }
}
