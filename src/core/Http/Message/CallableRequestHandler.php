<?php

namespace Core\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
class CallableRequestHandler implements RequestHandlerInterface
{
    /** @var callable */
    private $next;

    public function __construct(callable $next)
    {
        $this->next = $next;
    }

    public function handle(ServerRequestInterface $psrRequest): ResponseInterface
    {
        $response = ($this->next)(new Request($psrRequest));

        if (!$response instanceof ResponseInterface) {
            $response = new Response($response);
        }

        return $response instanceof Response ? $response->getPsrResponse() : $response;
    }
}
