<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Core\Http\Message\Response;

/**
 * @internal
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isCorsRequest($request)) {
            return $handler->handle($request);
        }

        if ($this->isPreflightRequest($request)) {
            return $this->handlePreflightRequest($request);
        }

        $response = $handler->handle($request);

        return $this->addCorsHeaders($request, $response);
    }

    protected function isCorsRequest(ServerRequestInterface $request): bool
    {
        return $request->hasHeader('Origin');
    }

    protected function isPreflightRequest(ServerRequestInterface $request): bool
    {
        return $request->getMethod() === 'OPTIONS' && $request->hasHeader('Access-Control-Request-Method');
    }

    protected function handlePreflightRequest(ServerRequestInterface $request): ResponseInterface
    {
        $response = new \Nyholm\Psr7\Response(204);
        return $this->addCorsHeaders($request, $response);
    }

    protected function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $config = config('cors', []);

        $allowedOrigins = $config['allowed_origins'] ?? ['*'];
        $allowedOrigin = '*';

        if (in_array('*', $allowedOrigins)) {
            $allowedOrigin = '*';
        } elseif (in_array($origin, $allowedOrigins)) {
            $allowedOrigin = $origin;
        }

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $config['allowed_methods'] ?? ['*']))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $config['allowed_headers'] ?? ['*']));

        if ($config['supports_credentials'] ?? false) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($config['max_age'] ?? 0) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $config['max_age']);
        }

        return $response;
    }
}
