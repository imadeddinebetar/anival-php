<?php

namespace Core\Http\Routing\Internal;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Core\Container\Internal\Application;
use Core\Http\Message\Request;
use Core\Http\Routing\Route;
use Core\Http\Routing\Pipeline;
use Core\Http\Routing\Contracts\ControllerResolverInterface;
use Core\Http\Routing\Contracts\RouteDispatcherInterface;
use Core\Http\Routing\Contracts\RequestDispatcherInterface;
use Core\Exceptions\Internal\HttpException;

/**
 * Orchestrates the full request dispatch cycle.
 *
 * Receives the matched route collection from the Router, resolves the
 * FastRoute dispatcher result, runs route-level middleware via Pipeline,
 * and delegates controller invocation to ControllerResolver.
 *
 * @internal
 */
class RequestDispatcher implements RequestDispatcherInterface
{
    public function __construct(
        protected Application $app,
        protected RouteDispatcherInterface $routeDispatcher,
        protected ControllerResolverInterface $controllerResolver,
    ) {}

    /**
     * @inheritDoc
     */
    public function dispatch(Request|ServerRequestInterface $request, array $routes, ?array $cachedRoutes = null): ResponseInterface
    {
        if ($request instanceof ServerRequestInterface) {
            $request = new Request($request);
        }

        // Bind the request to the container so the request() helper can access it
        $this->app->get('container')->set('request', $request);
        $this->app->get('container')->set(ServerRequestInterface::class, $request->getPsrRequest());

        // Use cached routes if available
        if ($cachedRoutes !== null && !empty($cachedRoutes)) {
            return $this->dispatchCached($request, $cachedRoutes);
        }

        $callback = function (RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route->getMethod(), $route->getUri(), $route);
            }
        };

        $method = $this->resolveMethod($request);
        $routeInfo = $this->routeDispatcher->dispatch($method, $request->getPath(), $callback);

        return $this->processDispatchResult($routeInfo, $request);
    }

    /**
     * Dispatch using cached routes.
     */
    protected function dispatchCached(Request $request, array $cachedRoutes): ResponseInterface
    {
        $filteredRoutes = array_filter($cachedRoutes, function ($key) {
            return !str_starts_with((string)$key, '__');
        }, ARRAY_FILTER_USE_KEY);

        $callback = function (RouteCollector $r) use ($filteredRoutes) {
            foreach ($filteredRoutes as $routeData) {
                // Route::fromArray needs a Router reference — use a lightweight shim
                $route = Route::fromArray($routeData);
                $r->addRoute($route->getMethod(), $route->getUri(), $route);
            }
        };

        $method = $this->resolveMethod($request);
        $routeInfo = $this->routeDispatcher->dispatch($method, $request->getPath(), $callback);

        return $this->processDispatchResult($routeInfo, $request);
    }

    /**
     * Resolve the real HTTP method, respecting _method overrides on POST.
     */
    private function resolveMethod(Request $request): string
    {
        $method = $request->getMethod();

        if ($method === 'POST') {
            $body = $request->getParsedBody();
            if (is_array($body) && isset($body['_method'])) {
                $method = strtoupper((string) $body['_method']);
            }
        }

        return $method;
    }

    /**
     * Process the FastRoute dispatch result into a response.
     */
    private function processDispatchResult(array $routeInfo, Request $request): ResponseInterface
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new HttpException(404);

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new HttpException(405);

            case Dispatcher::FOUND:
                /** @var Route $route */
                $route = $routeInfo[1];
                $vars = $routeInfo[2];

                // Inject route params and the matched Route object as attributes
                $request = $request->withAttribute('__route__', $route);
                foreach ($vars as $key => $value) {
                    $request = $request->withAttribute($key, $value);
                }

                // Update the container binding with the enriched request
                $this->app->get('container')->set('request', $request);
                $this->app->get('container')->set(ServerRequestInterface::class, $request->getPsrRequest());

                return $this->runRoute($request, $route);

            default:
                throw new HttpException(500); // @codeCoverageIgnore
        }
    }

    /**
     * Run the matched route through its middleware pipeline and invoke the action.
     */
    protected function runRoute(Request $request, Route $route): ResponseInterface
    {
        $action = $route->getAction();
        $middleware = $route->getMiddleware();

        return (new Pipeline($this->app))
            ->send($request)
            ->through($middleware)
            ->then(function ($req) use ($action) {
                // Ensure we always pass a Request object to the controller resolver,
                // even after PSR-15 middleware converts to ServerRequestInterface
                if (!$req instanceof Request) {
                    $req = new Request($req);
                }
                return $this->controllerResolver->resolve($req, $action);
            });
    }
}
