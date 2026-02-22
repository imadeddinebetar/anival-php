<?php

namespace Core\Http\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface KernelInterface extends RequestHandlerInterface
{
    public function bootstrap(): void;
    public function handle(ServerRequestInterface $request): ResponseInterface;
    public function handleException(\Throwable $e, ServerRequestInterface $request): ResponseInterface;

    /**
     * Perform any final actions after the response has been sent.
     */
    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;
}
