<?php

namespace Core\Http\Foundation;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Core\Http\Routing\Router;
use Core\Http\Routing\Pipeline;
use Core\Container\Internal\Application;
use Core\Config\Contracts\ConfigRepositoryInterface;
use Core\Exceptions\Internal\ExceptionHandler;
use Core\Http\Contracts\KernelInterface;
use Core\Http\Contracts\TerminableMiddleware;

/**
 * @internal
 */
class HttpKernel implements KernelInterface
{
    protected Application $app;
    protected Router $router;
    protected ConfigRepositoryInterface $config;
    protected ExceptionHandler $exceptionHandler;
    /** @var array<int, string> */
    protected array $middleware = [];
    /** @var array<int, object> Resolved middleware instances from the last handle() call */
    protected array $resolvedMiddleware = [];

    public function __construct(
        Application $app,
        ConfigRepositoryInterface $config,
        ?Router $router = null,
        array $globalMiddleware = []
    ) {
        $this->app = $app;
        $this->config = $config;
        $this->router = $router ?: $this->resolveRouter();
        $this->exceptionHandler = $this->resolveExceptionHandler();

        $this->middleware = $globalMiddleware;
    }

    protected function resolveExceptionHandler(): ExceptionHandler
    {
        if ($this->app->has(ExceptionHandler::class)) {
            return $this->app->get(ExceptionHandler::class);
        }

        $logger = $this->app->has('logger') ? $this->app->get('logger') : null;
        $handler = new ExceptionHandler($logger);
        $this->app->singleton(ExceptionHandler::class, $handler);

        return $handler;
    }

    protected function resolveRouter(): Router
    {
        if ($this->app->has(Router::class)) {
            return $this->app->get(Router::class);
        }

        // If not bound (shouldn't happen), try to make it
        $router = $this->app->make(Router::class);
        $this->app->singleton(Router::class, $router);

        return $router;
    }

    /**
     * Bootstrap the kernel.
     */
    public function bootstrap(): void
    {
        $this->loadRoutes();
    }

    protected function loadRoutes(): void
    {
        $cacheFile = $this->app->basePath('storage/framework/cache/routes.php');

        if (file_exists($cacheFile)) {
            $routes = require $cacheFile;
            $this->router->setRoutes($routes);
            return;
        }

        // Load web routes
        if (file_exists($this->app->basePath('routes/web.php'))) {
            $this->router->group([
                'middleware' => $this->config->get('app.middleware.web', [])
            ], function ($router) {
                require $this->app->basePath('routes/web.php');
            });
        }

        // Load API routes
        if (file_exists($this->app->basePath('routes/api.php'))) {
            $this->router->group([
                'prefix' => 'api',
                'middleware' => $this->config->get('app.middleware.api', [])
            ], function ($router) {
                require $this->app->basePath('routes/api.php');
            });
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            // Resolve middleware instances so terminate() can access them later
            $this->resolvedMiddleware = [];
            $resolvedPipes = [];
            foreach ($this->middleware as $pipe) {
                if (is_string($pipe)) {
                    $instance = $this->app->get($pipe);
                    $this->resolvedMiddleware[] = $instance;
                    $resolvedPipes[] = $instance;
                } else {
                    $resolvedPipes[] = $pipe;
                }
            }

            $response = (new Pipeline($this->app))
                ->send($request)
                ->through($resolvedPipes)
                ->then(fn($request) => $this->router->dispatch($request));

            return $response;
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    public function handleException(\Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        if ($response = $this->exceptionHandler->handle($e, $request)) {
            return $response;
        }

        $logger = $this->app->get('logger');
        if ($logger) {
            $logger->error($e->getMessage(), ['exception' => $e]);
        }

        return (new \Core\Exceptions\Internal\ExceptionRenderer($this->app))->render($request, $e);
    }

    /**
     * Perform any final actions after the response has been sent.
     *
     * Iterates all middleware resolved during handle() and calls terminate()
     * on any that implement TerminableMiddleware.
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void
    {
        foreach ($this->resolvedMiddleware as $middleware) {
            if ($middleware instanceof TerminableMiddleware) {
                $middleware->terminate($request, $response);
            }
        }
    }
}
