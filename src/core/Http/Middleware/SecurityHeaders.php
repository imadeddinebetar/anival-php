<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class SecurityHeaders implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $csp = config('app.csp', implode('; ', [
            "default-src 'self'",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "img-src 'self' data:",
            "connect-src 'self'",
        ]));

        if (env('APP_ENV') === 'local' && config('app.debug', false)) {
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline'",
                "font-src 'self'",
                "img-src 'self' data:",
                "connect-src 'self'",
            ]);
        }

        $response = $response
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $scheme = $request->getUri()->getScheme();
        if ($scheme === 'https') {
            $response = $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
