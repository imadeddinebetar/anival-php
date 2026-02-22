<?php

namespace Core\Http\Middleware;

use Core\Cache\Internal\RateLimiter;

use Core\Http\Message\Request;
use Core\Http\Message\Response;
use Closure;

/**
 * @internal
 */
class ThrottleRequests implements \Psr\Http\Server\MiddlewareInterface
{
    protected RateLimiter $limiter;
    protected int $maxAttempts;
    protected int $decayMinutes;
    protected array $trustedProxies;

    /**
     * @param RateLimiter $limiter
     */
    public function __construct(RateLimiter $limiter, int $maxAttempts = 60, int $decayMinutes = 1, ?array $trustedProxies = null)
    {
        $this->limiter = $limiter;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->trustedProxies = $trustedProxies ?? config('app.trusted_proxies', []);
    }

    /**
     * Handle the incoming request (PSR-15).
     */
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): \Psr\Http\Message\ResponseInterface
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $this->maxAttempts)) {
            return Response::json([
                'message' => 'Too Many Attempts.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429)->getPsrResponse();
        }

        $this->limiter->hit($key, $this->decayMinutes * 60);

        $response = $handler->handle($request);

        // Add rate limit headers
        return $response->withHeader('X-RateLimit-Limit', (string) $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', (string) $this->limiter->remainingAttempts($key, $this->maxAttempts));
    }

    /**
     * Handle the incoming request (for backward compatibility).
     */
    public function handle(Request $request, Closure $next, ?int $maxAttempts = null, ?int $decayMinutes = null): mixed
    {
        $maxAttempts = $maxAttempts ?? $this->maxAttempts;
        $decayMinutes = $decayMinutes ?? $this->decayMinutes;

        $key = $this->resolveRequestSignature($request->getPsrRequest());

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return Response::json([
                'message' => 'Too Many Attempts.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        if ($response instanceof \Psr\Http\Message\ResponseInterface) {
            $response = $response->withHeader('X-RateLimit-Limit', (string) $maxAttempts)
                ->withHeader('X-RateLimit-Remaining', (string) $this->limiter->remainingAttempts($key, $maxAttempts));
        }

        return $response;
    }

    /**
     * Resolve the request signature.
     */
    protected function resolveRequestSignature(\Psr\Http\Message\ServerRequestInterface $request): string
    {
        $ip = $this->getIp($request);

        return sha1(implode('|', [
            $request->getMethod(),
            $request->getUri()->getHost(),
            $request->getUri()->getPath(),
            $ip
        ]));
    }

    protected function getIp(\Psr\Http\Message\ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        $remoteAddr = $serverParams['REMOTE_ADDR'] ?? '127.0.0.1';

        if (!in_array($remoteAddr, $this->trustedProxies) && !in_array('*', $this->trustedProxies)) {
            return $remoteAddr;
        }

        // Check X-Forwarded-For first (most common)
        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded !== '') {
            $ips = array_map('trim', explode(',', $forwarded));
            return $ips[0];
        }

        // Fallback to X-Real-IP (used by some proxies like Nginx)
        $realIp = $request->getHeaderLine('X-Real-IP');
        if ($realIp !== '') {
            return trim($realIp);
        }

        return $remoteAddr;
    }
}
