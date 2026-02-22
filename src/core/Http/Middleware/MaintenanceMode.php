<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class MaintenanceMode implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $downFile = dirname(__DIR__, 4) . '/storage/framework/down';

        if (file_exists($downFile)) {
            $data = json_decode(file_get_contents($downFile), true);

            $message = $data['message'] ?? 'We\'ll be back soon!';
            $retry = $data['retry'] ?? null;

            $response = new \Core\Http\Message\Response($this->renderMaintenancePage($message, $retry), 503);
            $response = $response->withHeader('Content-Type', 'text/html');

            if ($retry) {
                $response = $response->withHeader('Retry-After', (string) $retry);
            }

            return $response;
        }

        return $handler->handle($request);
    }

    protected function renderMaintenancePage(string $message, ?int $retry = null): string
    {
        $retryText = $retry ? "<p>Please try again in {$retry} seconds.</p>" : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Mode</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            background: white;
            padding: 40px 60px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Maintenance Mode</h1>
        <p>{$message}</p>
        {$retryText}
    </div>
</body>
</html>
HTML;
    }
}
