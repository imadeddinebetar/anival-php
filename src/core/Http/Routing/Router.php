<?php

namespace Core\Http\Routing;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Core\Container\Internal\Application;
use Core\Http\Message\Request;
use Core\Exceptions\Internal\HttpException;
use Core\Http\Routing\Contracts\ControllerResolverInterface;
use Core\Http\Routing\Contracts\RouteDispatcherInterface;
use Core\Http\Routing\Contracts\RequestDispatcherInterface;
use Core\Http\Routing\Internal\ControllerResolver;
use Core\Http\Routing\Internal\RouteDispatcher;
use Core\Http\Routing\Internal\RequestDispatcher;
use Core\Config\Contracts\ConfigRepositoryInterface;
use Core\Database\Contracts\ModelBinderInterface;
use Core\Http\Contracts\RouterInterface;

/**
 * @internal
 */
class Router implements RouterInterface
{
    protected Application $app;
    protected RequestDispatcherInterface $requestDispatcher;
    /** @var array<int, Route> */
    protected array $routes = [];
    /** @var array<string, Route> */
    protected array $namedRoutes = [];
    /** @var array<int, array<string, mixed>> */
    protected array $groupStack = [];
    protected ?array $cachedRoutes = null;

    public function __construct(
        Application $app,
        ?ModelBinderInterface $modelBinder = null,
        ?ControllerResolverInterface $controllerResolver = null,
        ?RouteDispatcherInterface $routeDispatcher = null,
        ?RequestDispatcherInterface $requestDispatcher = null,
    ) {
        $this->app = $app;

        // Resolve model binder from the container if not provided
        if ($modelBinder === null && $app->has(ModelBinderInterface::class)) {
            $modelBinder = $app->get(ModelBinderInterface::class);
        }

        $resolvedControllerResolver = $controllerResolver ?: new ControllerResolver($app, $app->get(ConfigRepositoryInterface::class), $modelBinder);
        $resolvedRouteDispatcher = $routeDispatcher ?: new RouteDispatcher();

        $this->requestDispatcher = $requestDispatcher ?: new RequestDispatcher(
            $app,
            $resolvedRouteDispatcher,
            $resolvedControllerResolver,
        );

        $this->loadCachedRoutes();
    }

    /**
     * Load routes from cache if available
     */
    protected function loadCachedRoutes(): void
    {
        // Only load cached routes in production
        try {
            $env = $this->app->get('env');
            if ($env !== 'production') {
                return;
            }
        } catch (\Throwable $e) {
            return; // App not fully bootstrapped
        }

        $cachePath = $this->app->storagePath('framework/routes.php');

        if (!file_exists($cachePath)) {
            return;
        }

        // Check cache expiration (24 hours)
        $cached = @file_get_contents($cachePath);
        if ($cached === false) {
            return; // @codeCoverageIgnore
        }

        if (preg_match('/__cached_at\'\s*=>\s*(\d+)/', $cached, $matches)) {
            if (time() - (int)$matches[1] > 86400) {
                return; // Cache expired
            }
        }

        $this->cachedRoutes = require $cachePath;
    }

    /**
     * Check if cached routes are available
     */
    public function hasCachedRoutes(): bool
    {
        return $this->cachedRoutes !== null && !empty($this->cachedRoutes);
    }

    public function get(string $uri, string|callable|array $action): Route
    {
        return $this->addRoute('GET', $uri, $action);
    }

    public function post(string $uri, string|callable|array $action): Route
    {
        return $this->addRoute('POST', $uri, $action);
    }

    public function put(string $uri, string|callable|array $action): Route
    {
        return $this->addRoute('PUT', $uri, $action);
    }

    public function delete(string $uri, string|callable|array $action): Route
    {
        return $this->addRoute('DELETE', $uri, $action);
    }

    public function patch(string $uri, string|callable|array $action): Route
    {
        return $this->addRoute('PATCH', $uri, $action);
    }

    /**
     * Register a resource controller with standard CRUD routes.
     *
     * Generates: index, create, store, show, edit, update, destroy
     *
     * @param string $name     Resource name (e.g. 'photos')
     * @param string $controller Controller class
     * @param array  $options  Options: 'only', 'except'
     */
    public function resource(string $name, string $controller, array $options = []): void
    {
        $methods = [
            'index'   => ['GET',    "/{$name}"],
            'create'  => ['GET',    "/{$name}/create"],
            'store'   => ['POST',   "/{$name}"],
            'show'    => ['GET',    "/{$name}/{" . rtrim($name, 's') . "}"],
            'edit'    => ['GET',    "/{$name}/{" . rtrim($name, 's') . "}/edit"],
            'update'  => ['PUT',    "/{$name}/{" . rtrim($name, 's') . "}"],
            'destroy' => ['DELETE', "/{$name}/{" . rtrim($name, 's') . "}"],
        ];

        $methods = $this->filterResourceMethods($methods, $options);

        foreach ($methods as $action => [$httpMethod, $uri]) {
            $this->addRoute($httpMethod, $uri, [$controller, $action])
                ->name("{$name}.{$action}");
        }
    }

    /**
     * Register an API resource controller (no create/edit HTML form routes).
     *
     * @param string $name
     * @param string $controller
     * @param array  $options
     */
    public function apiResource(string $name, string $controller, array $options = []): void
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);
        $this->resource($name, $controller, $options);
    }

    /**
     * Filter resource methods by 'only' or 'except' options.
     *
     * @param array $methods
     * @param array $options
     * @return array
     */
    protected function filterResourceMethods(array $methods, array $options): array
    {
        if (isset($options['only'])) {
            $methods = array_intersect_key($methods, array_flip($options['only']));
        }

        if (isset($options['except'])) {
            $methods = array_diff_key($methods, array_flip($options['except']));
        }

        return $methods;
    }

    protected function addRoute(string $method, string $uri, string|callable|array $action): Route
    {
        $uri = $this->prefix($uri);

        $route = new Route(
            $method,
            $uri,
            $action,
            $this->gatherMiddleware(),
            $this
        );

        $this->routes[] = $route;

        return $route;
    }

    /** @param array<string, mixed> $attributes */
    public function group(array $attributes, callable $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    protected function prefix(string $uri): string
    {
        $prefix = '';
        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= '/' . trim($group['prefix'], '/');
            }
        }
        return rtrim($prefix . '/' . trim($uri, '/'), '/') ?: '/';
    }

    /** @return array<int, string> */
    protected function gatherMiddleware(): array
    {
        $middleware = [];
        foreach ($this->groupStack as $group) {
            if (isset($group['middleware'])) {
                $middleware = array_merge($middleware, (array) $group['middleware']);
            }
        }
        return $middleware;
    }

    public function dispatch(ServerRequestInterface|Request $request): ResponseInterface
    {
        $cachedRoutes = $this->hasCachedRoutes() ? $this->cachedRoutes : null;
        return $this->requestDispatcher->dispatch($request, $this->routes, $cachedRoutes);
    }

    public function registerNamedRoute(string $name, Route $route): void
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * @param array[] $routeDatas
     */
    public function setRoutes(array $routeDatas): void
    {
        $this->routes = [];
        $this->namedRoutes = [];

        foreach ($routeDatas as $data) {
            $route = Route::fromArray($data, $this);
            $this->routes[] = $route;
            if ($name = $route->getName()) {
                $this->namedRoutes[$name] = $route;
            }
        }
    }

    /**
     * @param string $name
     * @param array<string, mixed> $parameters
     * @return string
     */
    public function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route [{$name}] not defined.");
        }

        $uri = $this->namedRoutes[$name]->getUri();

        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', (string) $value, $uri);
        }

        $uri = '/' . ltrim($uri, '/');

        if ($absolute) {
            // Prepend APP_URL
            $config = $this->app->get('config');
            $root = $config->get('app.url') ?? '';
            $root = rtrim($root, '/');

            return $root . $uri;
        }

        return $uri;
    }
}
