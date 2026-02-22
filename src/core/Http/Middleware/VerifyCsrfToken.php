<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Nyholm\Psr7\Response;

/**
 * @internal
 */
class VerifyCsrfToken implements MiddlewareInterface
{
    protected CsrfTokenManager $tokenManager;
    /** @var array<int, string> */
    protected array $except = [];

    public function __construct(CsrfTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
        $this->except = array_merge($this->except, config('app.csrf_except', []));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->shouldSkip($request)) {
            return $handler->handle($request);
        }

        if ($this->tokensMatch($request)) {
            return $handler->handle($request);
        }

        return new Response(419, ['Content-Type' => 'text/html; charset=UTF-8'], 'CSRF token mismatch');
    }

    protected function shouldSkip(ServerRequestInterface $request): bool
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return true;
        }

        $path = $request->getUri()->getPath();
        foreach ($this->except as $except) {
            if ($path === $except || fnmatch($except, $path)) {
                return true;
            }
        }

        return false;
    }

    protected function tokensMatch(ServerRequestInterface $request): bool
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return false;
        }

        return $this->tokenManager->isTokenValid(
            new \Symfony\Component\Security\Csrf\CsrfToken('csrf', $token)
        );
    }

    protected function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $body = $request->getParsedBody();

        return $body['_token'] ?? $request->getHeaderLine('X-CSRF-TOKEN') ?: null;
    }
}
