<?php

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        // Prefer superglobals over getenv() for thread safety
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;

        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        if ($value === 'null') {
            return null;
        }

        return $value;
    }
}

if (!function_exists('container')) {
    /**
     * Get the application instance or a bound service.
     *
     * @param string|null $id
     * @return mixed|\Core\Container\Contracts\ContainerInterface
     */
    function container(?string $id = null): mixed
    {
        $app = \Core\Container\Internal\Application::getInstance();
        if ($app === null) {
            throw new \RuntimeException('Application not initialized');
        }

        if ($id === null) {
            return $app;
        }

        return $app->get($id);
    }
}

if (!function_exists('config')) {
    /**
     * Get / set the specified configuration value.
     *
     * If an array is passed as the key, we will assume you want to set configuration values.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function config(array|string|null $key = null, mixed $default = null): mixed
    {
        $app = \Core\Container\Internal\Application::getInstance();

        if (!$app) {
            return $default;
        }

        if (!$app->has('config')) {
            return $default;
        }

        $repository = $app->get('config');

        if (is_null($key)) {
            return $repository->all();
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $repository->set($k, $v);
            }
            return null;
        }

        return $repository->get($key, $default);
    }
}


if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        $app = \Core\Container\Internal\Application::getInstance();
        return $app ? $app->storagePath($path) : dirname(__DIR__, 2) . '/storage' . ($path ? '/' . $path : '');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $app = \Core\Container\Internal\Application::getInstance();
        return $app ? $app->basePath($path) : dirname(__DIR__, 2) . ($path ? '/' . $path : '');
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     *
     * @param  string  $path
     * @return string
     */
    function public_path(string $path = ''): string
    {
        $app = \Core\Container\Internal\Application::getInstance();
        return $app
            ? $app->publicPath($path)
            : dirname(__DIR__, 2) . '/public' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('assets')) {
    /**
     * Get the URL to an asset in the public/assets directory.
     *
     * @param string $path
     * @return string
     */
    function assets(string $path = ''): string
    {
        $root = config('app.url') ?? '';
        $root = rtrim($root, '/');
        $url  = $root . '/assets' . ($path ? '/' . ltrim($path, '/') : '');

        if ($path) {
            $file = rtrim(base_path('public/assets'), '/') . '/' . ltrim($path, '/');
            if (file_exists($file)) {
                $url .= '?v=' . filemtime($file);
            }
        }

        return $url;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return container()->get(\Symfony\Component\Security\Csrf\CsrfTokenManager::class)
            ->getToken('csrf')
            ->getValue();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('session')) {
    function session(?string $key = null, mixed $default = null): mixed
    {
        $session = container()->get(\Core\Session\Contracts\SessionInterface::class);
        if ($key === null) {
            return $session;
        }
        return $session->get($key, $default);
    }
}

if (!function_exists('auth')) {
    /** @return \Core\Auth\Contracts\AuthManagerInterface */
    function auth(): \Core\Auth\Contracts\AuthManagerInterface
    {
        return container()->get(\Core\Auth\Contracts\AuthManagerInterface::class);
    }
}

if (!function_exists('hasher')) {
    /** @return \Core\Auth\Contracts\HasherInterface */
    function hasher(): \Core\Auth\Contracts\HasherInterface
    {
        return container(\Core\Auth\Contracts\HasherInterface::class);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash the given value using bcrypt.
     *
     * @param string $value
     * @param array<string, mixed> $options
     * @return string
     */
    function bcrypt(string $value, array $options = []): string
    {
        $rounds = $options['rounds'] ?? 12;
        return password_hash($value, PASSWORD_BCRYPT, ['cost' => $rounds]);
    }
}

if (!function_exists('db')) {
    /** @return \Core\Database\Contracts\DatabaseManagerInterface */
    function db(): \Core\Database\Contracts\DatabaseManagerInterface
    {
        return container('db');
    }
}

if (!function_exists('tokens')) {
    /** @return \Core\Auth\Contracts\TokenManagerInterface */
    function tokens(): \Core\Auth\Contracts\TokenManagerInterface
    {
        return container(\Core\Auth\Contracts\TokenManagerInterface::class);
    }
}

if (!function_exists('websocket_token')) {
    /**
     * Generate a secure token for WebSocket authentication.
     *
     * @param int|string|null $userId
     * @return string
     */
    function websocket_token(int|string|null $userId = null): string
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) {
            throw new \RuntimeException('Authenticated user required for websocket_token()');
        }

        $appKey = config('app.key', '');
        $timestamp = time();
        $hmac = hash_hmac('sha256', "websocket:{$userId}:{$timestamp}", $appKey);

        return base64_encode("{$userId}:{$timestamp}:{$hmac}");
    }
}

if (!function_exists('gate')) {
    /** @return \Core\Auth\Contracts\GateInterface */
    function gate(): \Core\Auth\Contracts\GateInterface
    {
        return container(\Core\Auth\Contracts\GateInterface::class);
    }
}

if (!function_exists('view')) {
    /** @param array<string, mixed> $data
     *  @param array<string, mixed> $mergeData */
    function view(?string $view = null, array $data = [], array $mergeData = []): mixed
    {
        $factory = container()->get('view');

        if (func_num_args() === 0) {
            return $factory;
        }

        return $factory->render($view, $data);
    }
}

if (!function_exists('events')) {
    /** @return \Core\Events\Contracts\EventDispatcherInterface */
    function events(): \Core\Events\Contracts\EventDispatcherInterface
    {
        return container()->get(\Core\Events\Contracts\EventDispatcherInterface::class);
    }
}

if (!function_exists('route')) {
    /**
     * @param string $name
     * @param array<string, mixed> $parameters
     * @return string
     */
    function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return container()->get(\Core\Http\Routing\Router::class)->route($name, $parameters, $absolute);
    }
}

if (!function_exists('request')) {
    /**
     * Get the current request instance.
     *
     * @return \Core\Http\Message\Request
     */
    function request(): \Core\Http\Message\Request
    {
        return container()->get('request');
    }
}

if (!function_exists('method_field')) {
    /**
     * @param string $method
     * @return string
     */
    function method_field(string $method): string
    {
        return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
    }
}

if (!function_exists('redirect')) {
    /**
     * @param string|null $path
     * @param int $status
     * @return \Core\Http\Message\RedirectResponse
     */
    function redirect(?string $path = null, int $status = 302): \Core\Http\Message\RedirectResponse
    {
        return new \Core\Http\Message\RedirectResponse($path ?: '/', $status);
    }
}

if (!function_exists('getClientIp')) {
    /**
     * Get the client IP address, handling trusted proxies.
     *
     * @return string
     */
    function getClientIp(): string
    {
        $serverParams = $_SERVER;
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';

        $trustedProxies = [];
        try {
            $app = \Core\Container\Internal\Application::getInstance();
            if ($app !== null) {
                $appConfigPath = $app->configPath('app.php');
                if (file_exists($appConfigPath)) {
                    $appConfig = require $appConfigPath;
                    $trustedProxies = $appConfig['trusted_proxies'] ?? [];
                }
            }
        } catch (\Exception $e) {
            // Fallback to empty array
        }

        if (!in_array($remoteAddr, $trustedProxies) && !in_array('*', $trustedProxies)) {
            return $remoteAddr;
        }

        $forwarded = $serverParams['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            $ips = array_map('trim', explode(',', $forwarded));
            return array_shift($ips);
        }

        return $remoteAddr;
    }
}

if (!function_exists('back')) {
    /**
     * @param int $status
     * @return \Core\Http\Message\RedirectResponse
     */
    function back(int $status = 302): \Core\Http\Message\RedirectResponse
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($referer, $status);
    }
}

if (!function_exists('now')) {
    /**
     * Create a new Carbon instance for the current time.
     *
     * @return \Carbon\Carbon
     */
    function now(): \Carbon\Carbon
    {
        return \Carbon\Carbon::now();
    }
}

if (!function_exists('can')) {
    /**
     * Determine if the current user has a given ability.
     *
     * @param string $ability
     * @param mixed ...$args
     * @return bool
     */
    function can(string $ability, mixed ...$args): bool
    {
        return container()->get(\Core\Auth\Contracts\GateInterface::class)->allows($ability, ...$args);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param string $value
     * @return string
     */
    function encrypt(string $value): string
    {
        return _get_encrypter()->encrypt($value);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt the given value.
     *
     * @param string $value
     * @return string
     */
    function decrypt(string $value): string
    {
        return _get_encrypter()->decrypt($value);
    }
}

if (!function_exists('_get_encrypter')) {
    /**
     * Get or create the shared Encrypter instance, re-creating if the key changes.
     *
     * @return \Core\Security\Internal\Encrypter
     */
    function _get_encrypter(): \Core\Security\Internal\Encrypter
    {
        // Use $GLOBALS to avoid OPcache static variable caching issues
        // Prefer $_ENV directly to allow tests to swap keys easily, bypassing Env cache
        $key = $_ENV['APP_KEY'] ?? env('APP_KEY', '');

        if (
            !isset($GLOBALS['__anival_encrypter']) || !isset($GLOBALS['__anival_encrypter_key'])
            || $GLOBALS['__anival_encrypter_key'] !== $key
        ) {
            $GLOBALS['__anival_encrypter'] = new \Core\Security\Internal\Encrypter($key);
            $GLOBALS['__anival_encrypter_key'] = $key;
        }

        return $GLOBALS['__anival_encrypter'];
    }

    /**
     * Reset the static encrypter instance (for testing).
     */
    function _reset_encrypter(): void
    {
        // Clear the global cache
        unset($GLOBALS['__anival_encrypter'], $GLOBALS['__anival_encrypter_key']);
    }

    /**
     * Check if we're in test mode.
     */
    function _is_test_mode(): bool
    {
        return env('APP_ENV') === 'testing';
    }
}

if (!function_exists('rate_limiter')) {
    /** @return \Core\Cache\Contracts\RateLimiterInterface */
    function rate_limiter(): \Core\Cache\Contracts\RateLimiterInterface
    {
        return container()->get(\Core\Cache\Contracts\RateLimiterInterface::class);
    }
}

if (!function_exists('logger')) {
    /**
     * @param string|null $channel
     * @return mixed
     */
    function logger(?string $channel = null): mixed
    {
        $manager = container()->get(\Core\Log\Contracts\LogManagerInterface::class);

        if ($channel) {
            return $manager->channel($channel);
        }

        return $manager;
    }
}

if (!function_exists('log_context')) {
    /**
     * Get the current logging context.
     *
     * @return array
     */
    function log_context(): array
    {
        $context = [];

        // Get request ID if available
        try {
            $request = container()->get('request');
            if ($request && $request->getAttribute('request_id')) {
                $context['request_id'] = $request->getAttribute('request_id');
            }
        } catch (\Exception $e) {
            // Request not available
        }

        // Get user ID if available
        try {
            if (function_exists('auth')) {
                $userId = auth()->id();
                if ($userId) {
                    $context['user_id'] = $userId;
                }
            }
        } catch (\Exception $e) {
            // Auth not available
        }

        return $context;
    }
}

if (!function_exists('with_log_context')) {
    /**
     * Log with additional context.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    function with_log_context(string $level, string $message, array $context = []): void
    {
        $context = array_merge(log_context(), $context);
        logger()->$level($message, $context);
    }
}

if (!function_exists('queue')) {
    /** @return \Core\Queue\Contracts\QueueInterface */
    function queue(): \Core\Queue\Contracts\QueueInterface
    {
        return container()->get(\Core\Queue\Contracts\QueueInterface::class);
    }
}

if (!function_exists('dump')) {
    /**
     * @param  mixed  ...$vars
     * @return mixed
     */
    function dump(...$vars)
    {
        foreach ($vars as $v) {
            \Symfony\Component\VarDumper\VarDumper::dump($v);
        }

        if (count($vars) === 1) {
            return $vars[0];
        }

        return $vars;
    }
}

if (!function_exists('dd')) {
    /**
     * @param  mixed  ...$vars
     * @return void
     */
    function dd(...$vars)
    {
        foreach ($vars as $v) {
            \Symfony\Component\VarDumper\VarDumper::dump($v);
        }

        exit(1);
    }
}


if (!function_exists('first_only')) {
    /**
     * Return the first capital letter of a string.
     *
     * @param string $string
     * @return string
     */
    function first_only(string $string): string
    {
        return ucfirst(strtolower(substr($string, 0, 1)));
    }
}

// ---------------------------------------------------------------------------
// Missing helpers — abort, response, collect, old, trans, cache, etc.
// ---------------------------------------------------------------------------

if (!function_exists('abort')) {
    /**
     * Throw an HttpException with the given data.
     *
     * @param int $code HTTP status code
     * @param string $message
     * @param array<string, string> $headers (unused — kept for signature compat)
     * @return never
     * @throws \Core\Exceptions\Internal\HttpException
     */
    function abort(int $code, string $message = '', array $headers = []): never
    {
        throw new \Core\Exceptions\Internal\HttpException($code, $message);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Throw an HttpException if the given condition is true.
     *
     * @param bool $condition
     * @param int $code
     * @param string $message
     * @param array<string, string> $headers
     * @return void
     * @throws \Core\Exceptions\Internal\HttpException
     */
    function abort_if(bool $condition, int $code, string $message = '', array $headers = []): void
    {
        if ($condition) {
            abort($code, $message, $headers);
        }
    }
}

if (!function_exists('abort_unless')) {
    /**
     * Throw an HttpException unless the given condition is true.
     *
     * @param bool $condition
     * @param int $code
     * @param string $message
     * @param array<string, string> $headers
     * @return void
     * @throws \Core\Exceptions\Internal\HttpException
     */
    function abort_unless(bool $condition, int $code, string $message = '', array $headers = []): void
    {
        if (! $condition) {
            abort($code, $message, $headers);
        }
    }
}

if (!function_exists('response')) {
    /**
     * Return a new Response instance or the Response factory.
     *
     * When called with no arguments, returns a new empty Response.
     * When called with a body (string), returns a Response with that content.
     *
     * @param string|null $content
     * @param int $status
     * @param array<string, string> $headers
     * @return \Core\Http\Message\Response
     */
    function response(?string $content = null, int $status = 200, array $headers = []): \Core\Http\Message\Response
    {
        $response = new \Core\Http\Message\Response($content ?? '', $status);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}

if (!function_exists('collect')) {
    /**
     * Create a new Illuminate Collection instance.
     *
     * @param mixed $value
     * @return \Illuminate\Support\Collection
     */
    function collect(mixed $value = []): \Illuminate\Support\Collection
    {
        return new \Illuminate\Support\Collection($value);
    }
}

if (!function_exists('old')) {
    /**
     * Retrieve flashed input from the previous request.
     *
     * @param string|null $key
     * @param mixed $default
     * @return mixed
     */
    function old(?string $key = null, mixed $default = null): mixed
    {
        try {
            $session = container()->get(\Core\Session\Contracts\SessionInterface::class);
        } catch (\Exception $e) {
            return $default;
        }

        if ($key === null) {
            return $session->get('_old_input', []);
        }

        $oldInput = $session->get('_old_input', []);
        return $oldInput[$key] ?? $default;
    }
}

if (!function_exists('trans')) {
    /**
     * Translate the given message.
     *
     * Falls back to returning the key if no translation system is available.
     *
     * @param string|null $key
     * @param array<string, string> $replace
     * @param string|null $locale
     * @return string|array|null
     */
    function trans(?string $key = null, array $replace = [], ?string $locale = null): string|array|null
    {
        if ($key === null) {
            return $key;
        }

        // Attempt to resolve a translator from the container
        try {
            $app = \Core\Container\Internal\Application::getInstance();
            if ($app && $app->has('translator')) {
                $translator = $app->get('translator');
                return $translator->get($key, $replace, $locale);
            }
        } catch (\Exception $e) {
            // Fall through
        }

        // Fallback: perform simple placeholder replacement on the key itself
        $line = $key;
        foreach ($replace as $placeholder => $value) {
            $line = str_replace(':' . $placeholder, (string) $value, $line);
        }

        return $line;
    }
}

if (!function_exists('__')) {
    /**
     * Translate the given message (alias of trans()).
     *
     * @param string|null $key
     * @param array<string, string> $replace
     * @param string|null $locale
     * @return string|array|null
     */
    function __(string|null $key = null, array $replace = [], ?string $locale = null): string|array|null
    {
        return trans($key, $replace, $locale);
    }
}

if (!function_exists('cache')) {
    /**
     * Get / set a cache value.
     *
     * If called with no arguments, returns the CacheInterface instance.
     * If called with a string key, retrieves the cached value.
     * If called with an array, sets each key => value pair with optional TTL.
     *
     * @param mixed ...$args
     * @return mixed|\Core\Cache\Contracts\CacheInterface
     */
    function cache(mixed ...$args): mixed
    {
        $store = container()->get(\Core\Cache\Contracts\CacheInterface::class);

        if (count($args) === 0) {
            return $store;
        }

        // cache('key') — get
        if (count($args) === 1 && is_string($args[0])) {
            return $store->get($args[0]);
        }

        // cache('key', $default) — get with default
        if (count($args) === 2 && is_string($args[0]) && !is_array($args[0])) {
            return $store->get($args[0], $args[1]);
        }

        // cache(['key' => 'value'], $ttl) — put
        if (is_array($args[0])) {
            $ttl = $args[1] ?? null;
            foreach ($args[0] as $key => $value) {
                $store->set($key, $value, $ttl);
            }
            return true;
        }

        // cache('key', 'value', $ttl) — put single
        if (count($args) >= 2) {
            $store->set($args[0], $args[1], $args[2] ?? null);
            return true;
        }

        return $store;
    }
}

if (!function_exists('cookie')) {
    /**
     * Queue a cookie or get the CookieJar.
     *
     * When called with no arguments, returns the CookieJar instance.
     * Otherwise, queues a cookie with the given parameters.
     *
     * @param string|null $name
     * @param string $value
     * @param int $minutes
     * @param string|null $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool|null $httpOnly
     * @param string|null $sameSite
     * @return \Core\Http\Contracts\CookieJarInterface|null
     */
    function cookie(
        ?string $name = null,
        string $value = '',
        int $minutes = 0,
        ?string $path = null,
        ?string $domain = null,
        ?bool $secure = null,
        ?bool $httpOnly = null,
        ?string $sameSite = null
    ): ?\Core\Http\Contracts\CookieJarInterface {
        $jar = container()->get(\Core\Http\Contracts\CookieJarInterface::class);

        if ($name === null) {
            return $jar;
        }

        $jar->queue($name, $value, $minutes, $path, $domain, $secure, $httpOnly, $sameSite);
        return null;
    }
}

if (!function_exists('dispatch')) {
    /**
     * Dispatch a job to the queue.
     *
     * @param string|object $job Job class name or job instance
     * @param array<string, mixed> $data
     * @param string|null $queue
     * @return void
     */
    function dispatch(string|object $job, array $data = [], ?string $queue = null): void
    {
        $jobName = is_object($job) ? get_class($job) : $job;
        queue()->push($jobName, $data, $queue);
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event and call the listeners.
     *
     * @param string|object $event
     * @param mixed $payload
     * @return mixed
     */
    function event(string|object $event, mixed $payload = null): mixed
    {
        $eventName = is_object($event) ? get_class($event) : $event;
        return events()->dispatch($eventName, $payload ?? $event);
    }
}

if (!function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param mixed $target
     * @param string|array|int|null $key
     * @param mixed $default
     * @return mixed
     */
    function data_get(mixed $target, string|array|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', (string) $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if ($segment === null) {
                return $target;
            }

            if ($segment === '*') {
                if (!is_iterable($target)) {
                    return value($default);
                }

                $result = [];
                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? array_merge(...$result) : $result;
            }

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif ($target instanceof \ArrayAccess && $target->offsetExists($segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set an item on an array or object using "dot" notation.
     *
     * @param mixed $target
     * @param string|array $key
     * @param mixed $value
     * @param bool $overwrite
     * @return mixed
     */
    function data_set(mixed &$target, string|array $key, mixed $value, bool $overwrite = true): mixed
    {
        $segments = is_array($key) ? $key : explode('.', $key);
        $segment = array_shift($segments);

        if ($segment === '*') {
            if (!is_array($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (is_array($target)) {
            if ($segments) {
                if (!array_key_exists($segment, $target)) {
                    $target[$segment] = [];
                }
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !array_key_exists($segment, $target)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }
                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        }

        return $target;
    }
}

if (!function_exists('value')) {
    /**
     * Return the value if not a Closure, otherwise call the Closure and return its result.
     *
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    function value(mixed $value, mixed ...$args): mixed
    {
        return $value instanceof \Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('filled')) {
    /**
     * Determine if a value is "filled" (not blank).
     *
     * @param mixed $value
     * @return bool
     */
    function filled(mixed $value): bool
    {
        return !blank($value);
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param mixed $value
     * @return bool
     */
    function blank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof \Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('class_basename')) {
    /**
     * Get the class "basename" of the given object / class.
     *
     * @param string|object $class
     * @return string
     */
    function class_basename(string|object $class): string
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}

if (!function_exists('retry')) {
    /**
     * Retry an operation a given number of times.
     *
     * @param int $times
     * @param callable $callback
     * @param int|callable $sleepMilliseconds
     * @param callable|null $when
     * @return mixed
     * @throws \Exception
     */
    function retry(int $times, callable $callback, int|callable $sleepMilliseconds = 0, ?callable $when = null): mixed
    {
        $attempts = 0;

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (\Exception $e) {
            if ($times < 1 || ($when && !$when($e))) {
                throw $e;
            }

            $sleep = is_callable($sleepMilliseconds)
                ? $sleepMilliseconds($attempts, $e)
                : $sleepMilliseconds;

            if ($sleep) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     *
     * @param mixed $value
     * @param callable|null $callback
     * @return mixed
     */
    function tap(mixed $value, ?callable $callback = null): mixed
    {
        if ($callback === null) {
            return $value;
        }

        $callback($value);

        return $value;
    }
}

if (!function_exists('throw_if')) {
    /**
     * Throw the given exception if the given condition is true.
     *
     * @param bool $condition
     * @param \Throwable|string $exception
     * @param mixed ...$parameters
     * @return void
     * @throws \Throwable
     */
    function throw_if(bool $condition, \Throwable|string $exception = 'RuntimeException', mixed ...$parameters): void
    {
        if ($condition) {
            if (is_string($exception)) {
                throw new $exception(...$parameters);
            }
            throw $exception;
        }
    }
}

if (!function_exists('throw_unless')) {
    /**
     * Throw the given exception unless the given condition is true.
     *
     * @param bool $condition
     * @param \Throwable|string $exception
     * @param mixed ...$parameters
     * @return void
     * @throws \Throwable
     */
    function throw_unless(bool $condition, \Throwable|string $exception = 'RuntimeException', mixed ...$parameters): void
    {
        throw_if(! $condition, $exception, ...$parameters);
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current environment is Windows based.
     *
     * @return bool
     */
    function windows_os(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }
}

if (!function_exists('str')) {
    /**
     * Get a Stringable object from the given string or return the Illuminate Str class.
     *
     * @param string|null $string
     * @return \Illuminate\Support\Stringable|string
     */
    function str(?string $string = null): \Illuminate\Support\Stringable|string
    {
        if ($string === null) {
            return '';
        }

        return \Illuminate\Support\Str::of($string);
    }
}

if (!function_exists('head')) {
    /**
     * Get the first element of an array.
     *
     * @param array $array
     * @return mixed
     */
    function head(array $array): mixed
    {
        return reset($array);
    }
}

if (!function_exists('last')) {
    /**
     * Get the last element of an array.
     *
     * @param array $array
     * @return mixed
     */
    function last(array $array): mixed
    {
        return end($array);
    }
}

if (!function_exists('fake')) {
    /**
     * Get a Faker generator instance.
     *
     * @return \Core\Support\SimpleFaker
     */
    function fake(): object
    {
        static $faker = null;
        if ($faker === null) {
            $faker = new class {
                public function firstName(): string
                {
                    return 'John';
                }
                public function lastName(): string
                {
                    return 'Doe';
                }
                public function name(): string
                {
                    return 'John Doe';
                }
                public function email(): string
                {
                    return 'user_' . bin2hex(random_bytes(4)) . '@example.com';
                }
                public function password(): string
                {
                    return password_hash('password', PASSWORD_DEFAULT);
                }
                public function uuid(): string
                {
                    return bin2hex(random_bytes(16));
                }
                public function word(): string
                {
                    return 'test';
                }
                public function sentence(int $words = 6): string
                {
                    return 'This is a test sentence.';
                }
                public function paragraph(): string
                {
                    return 'This is a test paragraph for testing purposes.';
                }
                public function numberBetween(int $min = 0, int $max = 100): int
                {
                    return random_int($min, $max);
                }
                public function boolean(): bool
                {
                    return (bool)random_int(0, 1);
                }
                public function dateTime(): \DateTimeInterface
                {
                    return new \DateTime();
                }
                public function phoneNumber(): string
                {
                    return '+1234567890';
                }
                public function address(): string
                {
                    return '123 Test St, Test City';
                }
                public function url(): string
                {
                    return 'https://example.com';
                }
                public function text(int $maxLength = 200): string
                {
                    return substr(str_repeat('Lorem ipsum dolor sit amet. ', 10), 0, $maxLength);
                }
                public function unique(): object
                {
                    return $this;
                }
                public function optional(): object
                {
                    return $this;
                }
                public function safeEmail(): string
                {
                    return 'safe_' . bin2hex(random_bytes(4)) . '@example.com';
                }
            };
        }
        return $faker;
    }
}
