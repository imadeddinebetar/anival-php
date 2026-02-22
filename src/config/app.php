<?php

return [
    'name' => env('APP_NAME', 'Anival Framework'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'debug_bar' => env('APP_DEBUG_BAR', false),
    'url' => env('APP_URL'),
    'timezone' => 'UTC',
    'locale' => 'fr', // Default locale for translations
    'fallback_locale' => 'en', // Fallback locale for translations
    'log_level' => env('LOG_LEVEL', 'debug'),
    'controller_namespace' => 'App\Controllers',
    'key' => env('APP_KEY'),

    'providers' => [
        Bootstrap\Providers\EventServiceProvider::class,
        Bootstrap\Providers\LogServiceProvider::class,
        Bootstrap\Providers\TranslationServiceProvider::class,
        Bootstrap\Providers\ViewServiceProvider::class,
        Bootstrap\Providers\DatabaseServiceProvider::class,
        Bootstrap\Providers\CacheServiceProvider::class,
        Bootstrap\Providers\SessionServiceProvider::class,
        Bootstrap\Providers\ValidationServiceProvider::class,
        Bootstrap\Providers\ConsoleServiceProvider::class,
        Bootstrap\Providers\AuthServiceProvider::class,
        Bootstrap\Providers\CsrfServiceProvider::class,
        Bootstrap\Providers\QueueServiceProvider::class,
        Bootstrap\Providers\EncryptionServiceProvider::class,
        Bootstrap\Providers\DebugServiceProvider::class,

        // Custom Providers
        App\Providers\OrderEventServiceProvider::class,
    ],

    // Trusted proxy IPs for correct client IP resolution behind load balancers.
    // Set to ['*'] to trust all proxies (only if behind a known proxy/LB).
    'trusted_proxies' => array_filter(explode(',', env('TRUSTED_PROXIES', ''))),

    'middleware' => [
        'global' => [
            Core\Http\Middleware\CorsMiddleware::class,
            Core\Http\Middleware\RequestIdMiddleware::class,
            Core\Http\Middleware\SanitizeInput::class,
            Core\Http\Middleware\SecurityHeaders::class,
        ],
        'web' => [
            Core\Http\Middleware\SessionMiddleware::class,
            Core\Http\Middleware\VerifyCsrfToken::class,
            Core\Http\Middleware\DebugBarMiddleware::class,
        ],
        'api' => [
            Core\Http\Middleware\ThrottleRequests::class,
            Core\Http\Middleware\AuthenticateApi::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Aliases
    |--------------------------------------------------------------------------
    |
    | Short names you can use in route definitions instead of full class names.
    | Example: $router->get('/admin', ...)->middleware('auth');
    |
    */
    'middleware_aliases' => [
        'auth'     => App\Middleware\Authenticate::class,
        // 'guest'    => App\Middleware\RedirectIfAuthenticated::class,
        'throttle' => Core\Http\Middleware\ThrottleRequests::class,
        'csrf'     => Core\Http\Middleware\VerifyCsrfToken::class,
    ],
];
