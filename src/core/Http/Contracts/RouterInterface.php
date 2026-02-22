<?php

namespace Core\Http\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface
{
    public function get(string $uri, string|callable|array $action): mixed;
    public function post(string $uri, string|callable|array $action): mixed;
    public function put(string $uri, string|callable|array $action): mixed;
    public function delete(string $uri, string|callable|array $action): mixed;
    public function patch(string $uri, string|callable|array $action): mixed;
    public function group(array $attributes, callable $callback): void;
    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
