<?php

namespace Core\Log\Contracts;

use Psr\Log\LoggerInterface;

interface LogManagerInterface extends LoggerInterface
{
    /**
     * Get a log channel instance.
     */
    public function channel(?string $channel = null): LoggerInterface;

    /**
     * Create a new, on-demand aggregate logger instance.
     * @param array<int, string> $channels
     */
    public function stack(array $channels, ?string $channel = null): LoggerInterface;
}
