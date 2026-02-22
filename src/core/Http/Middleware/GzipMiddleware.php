<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Gzip Compression Middleware
 *
 * Compresses HTTP responses using gzip if the client supports it.
 * @internal
 */
class GzipMiddleware implements MiddlewareInterface
{
    protected int $level;
    protected array $excludeContentTypes = [];

    /**
     * @param int $level Compression level (0-9, default: 6)
     */
    public function __construct(int $level = 6)
    {
        $this->level = max(0, min(9, $level));
    }

    /**
     * Set content types to exclude from compression
     */
    public function excludeContentTypes(array $types): self
    {
        $this->excludeContentTypes = $types;
        return $this;
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check if client accepts gzip
        $acceptEncoding = $request->getHeaderLine('Accept-Encoding');

        if (!$this->supportsGzip($acceptEncoding)) {
            return $handler->handle($request);
        }

        $response = $handler->handle($request);

        // Skip already compressed responses
        if ($this->isAlreadyCompressed($response)) {
            return $response;
        }

        // Skip excluded content types
        if ($this->shouldExclude($response)) {
            return $response;
        }

        // Compress the response
        return $this->compress($response);
    }

    /**
     * Check if client supports gzip
     */
    protected function supportsGzip(string $acceptEncoding): bool
    {
        return stripos($acceptEncoding, 'gzip') !== false;
    }

    /**
     * Check if response is already compressed
     */
    protected function isAlreadyCompressed(ResponseInterface $response): bool
    {
        $contentEncoding = $response->getHeaderLine('Content-Encoding');

        return !empty($contentEncoding) &&
               stripos($contentEncoding, 'gzip') !== false;
    }

    /**
     * Check if response should be excluded from compression
     */
    protected function shouldExclude(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');

        foreach ($this->excludeContentTypes as $type) {
            if (stripos($contentType, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compress the response body
     */
    protected function compress(ResponseInterface $response): ResponseInterface
    {
        $body = $response->getBody();

        // Seek to beginning if possible
        if (method_exists($body, 'seek')) {
            $body->seek(0);
        }

        $content = (string) $body;

        // Skip compression for very small responses
        if (strlen($content) < 256) {
            return $response;
        }

        // Compress using gzip
        $compressed = gzencode($content, $this->level);

        if ($compressed === false) {
            return $response;
        }

        // Update response
        $newResponse = $response->withHeader('Content-Encoding', 'gzip');
        $newResponse = $newResponse->withHeader('Vary', 'Accept-Encoding');
        $newResponse = $newResponse->withHeader('X-Content-Type-Options', 'nosniff');

        // Update Content-Length if present
        if ($response->hasHeader('Content-Length')) {
            $newResponse = $newResponse->withHeader('Content-Length', strlen($compressed));
        }

        // Replace body
        $newResponse->getBody()->write($compressed);

        return $newResponse;
    }
}
