<?php

namespace Core\Debug\Internal\Collectors;

/**
 * RouteCollector - Collects route information for the debug bar
 */
class RouteCollector
{
    protected array $route = [];

    public function setRoute(array $route): void
    {
        $this->route = $route;
    }

    public function collect(): array
    {
        return [
            'route' => $this->route,
            'name' => $this->route['name'] ?? null,
            'path' => $this->route['path'] ?? null,
            'methods' => $this->route['methods'] ?? [],
            'middleware' => $this->route['middleware'] ?? [],
            'controller' => $this->route['handler'] ?? null,
        ];
    }
}
