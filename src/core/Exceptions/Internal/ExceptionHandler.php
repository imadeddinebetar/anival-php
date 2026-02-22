<?php

namespace Core\Exceptions\Internal;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Core\Exceptions\Contracts\ExceptionHandlerInterface;

/**
 * @internal
 */
class ExceptionHandler implements ExceptionHandlerInterface
{
    /** @var array<string, callable> */
    protected array $handlers = [];

    protected ?LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Register a custom handler for a specific exception type.
     */
    public function register(string $exception, callable $handler): void
    {
        $this->handlers[$exception] = $handler;
    }

    /**
     * Handle the exception and return a response if a custom handler is found.
     */
    public function handle(\Throwable $e, ServerRequestInterface $request): ?ResponseInterface
    {
        // Always log the exception
        $this->report($e);

        foreach ($this->handlers as $type => $handler) {
            if ($e instanceof $type) {
                return $handler($e, $request);
            }
        }

        return null;
    }

    /**
     * Report/log the exception.
     */
    protected function report(\Throwable $e): void
    {
        if ($this->logger === null) {
            return;
        }

        $this->logger->error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}
