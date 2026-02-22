<?php

namespace Core\Http\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Core\Support\Pipeline as BasePipeline;
use Core\Http\Message\Request;

/**
 * HTTP middleware pipeline.
 *
 * Extends the generic pipeline with PSR-15 MiddlewareInterface support
 * and Core\Http\Message\Request conversion.
 * @internal
 */
class Pipeline extends BasePipeline
{
    public function then(callable $destination): ResponseInterface
    {
        /** @var ResponseInterface */
        return parent::then($destination);
    }

    protected function carry(): \Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                if (is_string($pipe)) {
                    $pipe = $this->container->get($pipe);
                }

                $handler = new CallableRequestHandler($stack);

                if ($pipe instanceof \Psr\Http\Server\MiddlewareInterface) {
                    $psrRequest = $passable instanceof Request ? $passable->getPsrRequest() : $passable;
                    $psrResponse = $pipe->process($psrRequest, $handler);
                    return $psrResponse;
                }

                if (method_exists($pipe, 'handle')) {
                    $request = $passable instanceof Request ? $passable : new Request($passable);
                    return $pipe->handle($request, $stack);
                }

                if (is_callable($pipe)) {
                    return $pipe($passable, $stack);
                }

                throw new \RuntimeException(
                    'Middleware must implement MiddlewareInterface.'
                );
            };
        };
    }
}
