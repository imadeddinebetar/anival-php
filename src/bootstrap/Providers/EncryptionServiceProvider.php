<?php

namespace Bootstrap\Providers;

use Core\Security\Internal\Encrypter;
use Core\Security\Contracts\EncrypterInterface;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Encrypter::class, function () {
            $key = config('app.key');

            if (empty($key)) {
                throw new \RuntimeException('No application encryption key has been specified.');
            }

            return new Encrypter($key);
        });

        $this->app->bind(EncrypterInterface::class, Encrypter::class);
        $this->app->bind('encrypter', EncrypterInterface::class);
    }
}
