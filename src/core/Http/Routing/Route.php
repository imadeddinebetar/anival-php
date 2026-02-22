<?php

namespace Core\Http\Routing;

/**
 * @phpstan-consistent-constructor
 * @internal
 */
class Route
{
    protected string $method;
    protected string $uri;
    protected mixed $action;
    protected array $middleware;
    protected ?string $name = null;
    protected ?Router $router;

    /**
     * @param string $method
     * @param string $uri
     * @param mixed $action
     * @param array $middleware
     * @param Router|null $router
     */
    public function __construct(string $method, string $uri, mixed $action, array $middleware, ?Router $router = null)
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->action = $action;
        $this->middleware = $middleware;
        $this->router = $router;
    }

    /**
     * Set the name of the route.
     *
     * @param string $name
     * @return $this
     */
    public function name(string $name): self
    {
        $this->name = $name;
        $this->router?->registerNamedRoute($name, $this);
        return $this;
    }

    /**
     * Add middleware to the route.
     *
     * @param array|callable|string $middleware
     * @return $this
     */
    public function middleware(array|callable|string $middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }

        return $this;
    }

    /**
     * Get the route's name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the route's URI.
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the route's method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the route's action.
     *
     * @return mixed
     */
    public function getAction(): mixed
    {
        return $this->action;
    }

    /**
     * Get the route's middleware.
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Convert the route to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'uri' => $this->uri,
            'action' => $this->action,
            'middleware' => $this->middleware,
            'name' => $this->name,
        ];
    }

    /**
     * @param array $data
     * @param Router|null $router
     * @return static
     */
    public static function fromArray(array $data, ?Router $router = null): static
    {
        $route = new static(
            $data['method'],
            $data['uri'],
            $data['action'],
            $data['middleware'],
            $router
        );

        if (isset($data['name'])) {
            $route->name($data['name']);
        }

        return $route;
    }
}
