<?php

namespace Core\Http\Middleware;

use Core\Debug\Internal\DebugBar;
use Core\Container\Internal\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Nyholm\Psr7\Stream;

/**
 * @internal
 */
class DebugBarMiddleware implements MiddlewareInterface
{
    protected Application $app;
    protected DebugBar $debugBar;

    public function __construct(Application $app, DebugBar $debugBar)
    {
        $this->app = $app;
        $this->debugBar = $debugBar;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$this->debugBar->isEnabled()) {
            return $response;
        }

        if ($this->isJsonResponse($response)) {
            return $response;
        }

        if ($this->isHtmlResponse($response)) {
            return $this->injectDebugBar($response);
        }

        return $response;
    }

    protected function isJsonResponse(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');
        return str_contains($contentType, 'application/json');
    }

    protected function isHtmlResponse(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');
        
        // If no content type is set, we assume it's HTML if it's not JSON
        if (empty($contentType)) {
            return true;
        }

        return str_contains($contentType, 'text/html');
    }

    protected function injectDebugBar(ResponseInterface $response): ResponseInterface
    {
        $content = (string) $response->getBody();
        $debugBarHtml = $this->debugBar->render();

        if (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $debugBarHtml . '</body>', $content);
        } else {
            $content .= $debugBarHtml;
        }

        $body = Stream::create($content);
        return $response->withBody($body);
    }
}
