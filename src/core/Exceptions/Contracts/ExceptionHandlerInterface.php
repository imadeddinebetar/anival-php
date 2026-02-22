<?php

namespace Core\Exceptions\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Contract for the exception handler service.
 */
interface ExceptionHandlerInterface
{
    /**
     * Register a custom handler for a specific exception type.
     */
    public function register(string $exception, callable $handler): void;

    /**
     * Handle the exception and return a response if a custom handler is found.
     */
    public function handle(\Throwable $e, ServerRequestInterface $request): ?ResponseInterface;
}
