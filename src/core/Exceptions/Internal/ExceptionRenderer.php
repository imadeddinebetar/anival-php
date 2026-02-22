<?php

namespace Core\Exceptions\Internal;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Core\Exceptions\Internal\HttpException;
use Core\View\Internal\View;
use Nyholm\Psr7\Stream;
use Core\Container\Internal\Application;

/**
 * @internal
 */
class ExceptionRenderer
{
    public function __construct(private Application $app) {}

    public function render(ServerRequestInterface $request, \Throwable $e): ResponseInterface
    {
        if ($this->wantsJson($request)) {
            return $this->renderJson($e);
        }

        return $this->renderHtml($e);
    }

    private function renderJson(\Throwable $e): ResponseInterface
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;
        $debug = config('app.debug', false);
        $isProduction = config('app.env', 'local') === 'production';

        return $this->responseFactory()->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->createStream(json_encode([
                'error' => ($debug || !$isProduction) ? $e->getMessage() : 'An error occurred.',
                'trace' => $debug ? $e->getTraceAsString() : null,
            ]) ?: ''));
    }

    private function renderHtml(\Throwable $e): ResponseInterface
    {
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;
        $isProduction = config('app.env', 'local') === 'production';

        if ($statusCode === 500 && !$isProduction) {
            return $this->renderDebugPage($e);
        }

        try {
            $view = $this->app->get(View::class);
            $viewName = 'errors.' . $statusCode;

            if ($view->exists($viewName)) {
                $html = $view->render($viewName, [
                    'exception' => $e,
                    'statusCode' => $statusCode,
                    'message' => $e->getMessage(),
                ]);

                return $this->responseFactory()->createResponse($statusCode)
                    ->withHeader('Content-Type', 'text/html; charset=UTF-8')
                    ->withBody($this->createStream($html));
            }
        } catch (\Throwable) {
            // View system unavailable — fall through to generic response
        }

        return $this->renderGeneric($statusCode, $e);
    }

    private function renderDebugPage(\Throwable $e): ResponseInterface
    {
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace = '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>500 - Internal Server Error</title>
<style>body{font-family:system-ui,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9fa;color:#333}
.container{text-align:center;width:90%;padding:2rem}.code{font-size:4rem;font-weight:700;color:#dc3545}
.msg{margin:1rem 0;font-size:1.1rem}pre{text-align:left;background:#e9ecef;padding:1rem;border-radius:.5rem;overflow-x:auto;font-size:.85rem}</style>
</head>
<body><div class="container"><div class="code">500</div><div class="msg">{$message}</div>{$trace}</div></body>
</html>
HTML;

        return $this->responseFactory()->createResponse(500)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withBody($this->createStream($html));
    }

    private function renderGeneric(int $statusCode, \Throwable $e): ResponseInterface
    {
        $label = htmlspecialchars($e->getMessage() ?: 'An unexpected error occurred.', ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{$statusCode} Error</title>
<style>body{font-family:system-ui,sans-serif;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9fa;color:#333}
.container{text-align:center;padding:2rem}.code{font-size:4rem;font-weight:700;color:#dc3545}.msg{margin:1rem 0;font-size:1.1rem}</style>
</head>
<body><div class="container"><div class="code">{$statusCode}</div><div class="msg">{$label}</div></div></body>
</html>
HTML;

        return $this->responseFactory()->createResponse($statusCode)
            ->withHeader('Content-Type', 'text/html; charset=UTF-8')
            ->withBody($this->createStream($html));
    }

    protected function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');
        $path = $request->getUri()->getPath();

        if (str_starts_with($path, '/api/') || $path === '/api') {
            return true;
        }

        if (str_contains($accept, 'application/json') || str_contains($accept, '+json')) {
            return true;
        }

        if ($request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest') {
            return true;
        }

        return false;
    }

    private function responseFactory(): ResponseFactoryInterface
    {
        return $this->app->get('response.factory');
    }

    private function createStream(string $content): \Psr\Http\Message\StreamInterface
    {
        return Stream::create($content);
    }
}
