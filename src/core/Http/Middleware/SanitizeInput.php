<?php

namespace Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class SanitizeInput implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $data = $request->getParsedBody();

        if (is_array($data)) {
            $data = $this->sanitize($data);
            $request = $request->withParsedBody($data);
        }

        $queryParams = $request->getQueryParams();
        if (!empty($queryParams)) {
            $queryParams = $this->sanitize($queryParams);
            $request = $request->withQueryParams($queryParams);
        }

        return $handler->handle($request);
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed> */
    protected function sanitize(array $data): array
    {
        return array_map(function ($value) {
            if (is_array($value)) {
                return $this->sanitize($value);
            }

            if (is_string($value)) {
                // Remove null bytes
                $value = str_replace(chr(0), '', $value);
                // Trim whitespace
                $value = trim($value);

                // Empty to Null
                if ($value === '') {
                    $value = null;
                }
            }

            return $value;
        }, $data);
    }
}
