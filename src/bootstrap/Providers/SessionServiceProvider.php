<?php

namespace Bootstrap\Providers;

use Core\Session\Internal\SessionManager;

class SessionServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(\Core\Session\Contracts\SessionInterface::class, SessionManager::class);
        $this->app->singleton(SessionManager::class, SessionManager::class);
        $this->app->bind('session', \Core\Session\Contracts\SessionInterface::class);
    }
}
