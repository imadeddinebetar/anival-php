<?php

namespace Core\Http\Middleware;

use Core\Http\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Response Cache Middleware
 *
 * Caches HTTP responses for static or semi-static pages.
 * Uses cache tags for easy cache invalidation.
 * @internal
 */
class ResponseCache implements MiddlewareInterface
{
    protected $cache;
    protected int $ttl;
    protected array $except = [];
    protected array $only = [];

    /**
     * @param int $ttl Cache TTL in seconds (default: 1 hour)
     */
    public function __construct($cache, int $ttl = 3600)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * Set paths that should not be cached
     */
    public function except(array $paths): self
    {
        $this->except = $paths;
        return $this;
    }

    /**
     * Set paths that should be cached (only these)
     */
    public function only(array $paths): self
    {
        $this->only = $paths;
        return $this;
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Skip non-GET requests
        if ($request->getMethod() !== 'GET') {
            return $handler->handle($request);
        }

        // Check if this route should be cached
        if (!$this->shouldCache($request)) {
            return $handler->handle($request);
        }

        // Generate cache key
        $key = $this->getCacheKey($request);

        // Check for cached response
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            return $this->createResponseFromCache($cached);
        }

        // Generate new response
        $response = $handler->handle($request);

        // Only cache successful responses
        if ($response->getStatusCode() === 200) {
            $this->cacheResponse($key, $response);
        }

        return $response;
    }

    /**
     * Determine if the request should be cached
     */
    protected function shouldCache(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        // Check except paths
        foreach ($this->except as $pattern) {
            if ($this->matchesPattern($path, $pattern)) {
                return false;
            }
        }

        // If only() is specified, only cache those paths
        if (!empty($this->only)) {
            foreach ($this->only as $pattern) {
                if ($this->matchesPattern($path, $pattern)) {
                    return true;
                }
            }
            return false;
        }

        return true;
    }

    /**
     * Match path against pattern
     */
    protected function matchesPattern(string $path, string $pattern): bool
    {
        // Convert to regex
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $pattern = str_replace('/', '\\/', $pattern);

        return (bool) preg_match("/^{$pattern}$/", $path);
    }

    /**
     * Generate cache key from request
     */
    protected function getCacheKey(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery() ?? '';

        return 'response:' . md5($path . '?' . $query);
    }

    /**
     * Cache the response
     */
    protected function cacheResponse(string $key, ResponseInterface $response): void
    {
        $data = [
            'status' => $response->getStatusCode(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
        ];

        $this->cache->set($key, serialize($data), $this->ttl);
    }

    /**
     * Create response from cached data
     */
    protected function createResponseFromCache(string $cached): ResponseInterface
    {
        $data = unserialize($cached);

        $response = new Response($data['body'], $data['status'], $data['headers']);

        // Add cache hit header for debugging
        $response = $response->withHeader('X-Cache', 'HIT');

        return $response;
    }

    /**
     * Clear cache for a specific path
     */
    public function forget(string $path): void
    {
        $key = 'response:' . md5($path);
        $this->cache->delete($key);
    }

    /**
     * Clear all response cache
     */
    public function clear(): void
    {
        // This would require iteration over cache keys
        // For tagged cache, use: $this->cache->clear();
    }
}
