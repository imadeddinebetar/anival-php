<?php

namespace Bootstrap\Providers;

use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;

class CsrfServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(CsrfTokenManager::class, function () {
            return new CsrfTokenManager(
                new UriSafeTokenGenerator(),
                new \Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage()
            );
        });
    }
}
