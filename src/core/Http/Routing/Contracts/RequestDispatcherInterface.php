<?php

namespace Core\Http\Routing\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Core\Http\Message\Request;

/**
 * Orchestrates the full request dispatch cycle: resolves the matched route
 * from the route collection, runs route-level middleware, and invokes the
 * controller action.
 */
interface RequestDispatcherInterface
{
    /**
     * Dispatch the given request against the provided route collection.
     *
     * @param Request|ServerRequestInterface $request
     * @param array<int, \Core\Http\Routing\Route> $routes
     * @param array|null $cachedRoutes
     * @return ResponseInterface
     */
    public function dispatch(Request|ServerRequestInterface $request, array $routes, ?array $cachedRoutes = null): ResponseInterface;
}
