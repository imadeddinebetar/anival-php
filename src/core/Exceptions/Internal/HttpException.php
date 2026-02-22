<?php

namespace Core\Exceptions\Internal;

/**
 * @internal
 */
class HttpException extends \RuntimeException
{
    protected int $statusCode;

    public function __construct(
        int $statusCode,
        string $message = '',
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        parent::__construct($message ?: $this->defaultMessage($statusCode), $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    private function defaultMessage(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'HTTP Error',
        };
    }
}
