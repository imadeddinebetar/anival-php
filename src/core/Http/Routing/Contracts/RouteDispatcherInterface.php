<?php

namespace Core\Http\Routing\Contracts;

interface RouteDispatcherInterface
{
    /**
     * Dispatch the request against the route definitions.
     *
     * @param string $httpMethod
     * @param string $uri
     * @param callable $routeDefinitionCallback
     * @return array The route info: [status, handler, vars]
     */
    public function dispatch(string $httpMethod, string $uri, callable $routeDefinitionCallback): array;
}
