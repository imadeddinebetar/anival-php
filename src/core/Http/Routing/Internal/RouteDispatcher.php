<?php

namespace Core\Http\Routing\Internal;

use Core\Http\Routing\Contracts\RouteDispatcherInterface;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

/**
 * @internal
 */
class RouteDispatcher implements RouteDispatcherInterface
{
    /**
     * Dispatch the request against the route definitions.
     *
     * @param string $httpMethod
     * @param string $uri
     * @param callable $routeDefinitionCallback
     * @return array The route info: [status, handler, vars]
     */
    public function dispatch(string $httpMethod, string $uri, callable $routeDefinitionCallback): array
    {
        $dispatcher = simpleDispatcher($routeDefinitionCallback);
        return $dispatcher->dispatch($httpMethod, $uri);
    }
}
