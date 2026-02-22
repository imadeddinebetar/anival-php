<?php

namespace Bootstrap\Providers;

use Core\Cache\Internal\CacheManager;
use Core\Cache\Internal\RateLimiter;
use Core\Cache\Contracts\CacheInterface;
use Core\Cache\Contracts\RateLimiterInterface;

class CacheServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->app->singleton(CacheManager::class, function () {
            $driver = config('cache.default', 'file');
            $config = config('cache.stores.' . $driver, []);
            if ($driver === 'file' && !isset($config['path'])) {
                $config['path'] = storage_path('cache');
            }
            return new CacheManager($driver, $config);
        });

        $this->app->bind(CacheInterface::class, CacheManager::class);
        $this->app->bind('cache', CacheInterface::class);

        $this->app->singleton(RateLimiter::class, RateLimiter::class);
        $this->app->bind(RateLimiterInterface::class, RateLimiter::class);
        $this->app->bind('limiter', RateLimiterInterface::class);
    }
}
