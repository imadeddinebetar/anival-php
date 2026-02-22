<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class RequestIdMiddleware implements MiddlewareInterface
{
    public const REQUEST_ID_HEADER = 'X-Request-ID';
    public const REQUEST_ID_ATTRIBUTE = 'request_id';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $this->resolveRequestId($request);

        $request = $request->withAttribute(self::REQUEST_ID_ATTRIBUTE, $requestId);

        $response = $handler->handle($request);

        return $response->withHeader(self::REQUEST_ID_HEADER, $requestId);
    }

    protected function resolveRequestId(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine(self::REQUEST_ID_HEADER);

        if (!empty($header)) {
            return $header;
        }

        return $this->generateRequestId();
    }

    protected function generateRequestId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
