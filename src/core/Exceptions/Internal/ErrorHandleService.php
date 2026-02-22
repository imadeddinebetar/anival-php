<?php

namespace Core\Exceptions\Internal;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Responsible for rendering error responses when the kernel is unavailable
 * (e.g. during application boot, before the kernel is instantiated).
 *
 * When the kernel IS available, error rendering is delegated to
 * HttpKernel::handleException() which supports custom Blade views.
 *
 * Single Responsibility: produce an HTTP error response from a Throwable.
 */
/**
 * @internal
 */
class ErrorHandleService
{
    /**
     * Attempt to render an error response.
     *
     * If a kernel instance is available it is used so that custom Blade error
     * views (resources/views/errors/{code}.blade.php) are respected.
     * Otherwise a minimal self-contained HTML page is emitted and the process
     * exits immediately.
     *
     * @param \Throwable                  $e
     * @param HttpKernel|null             $kernel  Kernel instance, if already created.
     * @param ServerRequestInterface|null $request PSR-7 request, if already created.
     * @return ResponseInterface|null     Returns a response when the kernel handled it,
     *                                    null when the fallback path called exit().
     */
    public static function handle(
        \Throwable $e,
        ?HttpKernel $kernel,
        ?ServerRequestInterface $request
    ): ?ResponseInterface {
        if ($kernel !== null && $request !== null) {
            return $kernel->handleException($e, $request);
        }

        self::emitFallback($e);

        return null; // unreachable – emitFallback() exits
    }

    /**
     * Emit a minimal error page when the kernel is not yet available.
     * Respects APP_ENV and APP_DEBUG environment variables.
     */
    private static function emitFallback(\Throwable $e): never
    {
        $isProduction = ($_ENV['APP_ENV'] ?? 'local') === 'production';
        $isDebug      = filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN);

        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');

        echo self::buildHtml($e, $isProduction, $isDebug);

        exit(1);
    }

    private static function buildHtml(\Throwable $e, bool $isProduction, bool $isDebug): string
    {
        $style = '<style>'
            . 'body{font-family:system-ui,sans-serif;margin:0;display:flex;align-items:center;'
            . 'justify-content:center;min-height:100vh;background:#f8f9fa;color:#333}'
            . '.box{text-align:center;padding:2rem}'
            . '.code{font-size:4rem;font-weight:700;color:#dc3545}'
            . '.msg{margin:1rem 0;font-size:1.1rem}'
            . 'pre{text-align:left;background:#e9ecef;padding:1rem;border-radius:.5rem;'
            . 'overflow-x:auto;font-size:.85rem}'
            . '</style>';

        if ($isProduction) {
            return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
                . '<title>500 - Server Error</title>' . $style . '</head>'
                . '<body><div class="box"><div class="code">500</div>'
                . '<div class="msg">An unexpected error occurred.</div></div></body></html>';
        }

        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        $trace   = $isDebug
            ? '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8') . '</pre>'
            : '';

        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">'
            . '<title>500 - Server Error</title>' . $style . '</head>'
            . '<body><div class="box"><div class="code">500</div>'
            . '<div class="msg">' . $message . '</div>' . $trace . '</div></body></html>';
    }
}
