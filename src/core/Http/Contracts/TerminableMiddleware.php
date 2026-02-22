<?php

namespace Core\Http\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware that needs to perform work after the response has been sent.
 *
 * Implement this interface on any middleware that should run cleanup,
 * logging, session flushing, or other post-response tasks.
 */
interface TerminableMiddleware
{
    /**
     * Perform any final actions after the response has been sent to the client.
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;
}
