<?php

namespace Core\WebSocket\Contracts;

/**
 * Contract for the WebSocket handler service.
 *
 * Note: Method signatures use mixed types to avoid coupling to
 * Workerman-specific types (Worker, TcpConnection). Implementations
 * should type-hint the concrete Workerman types in their methods.
 */
interface WebSocketHandlerInterface
{
    /**
     * Handle worker start event.
     */
    public function onWorkerStart(mixed $worker): void;

    /**
     * Handle new WebSocket connection.
     */
    public function onConnect(mixed $connection): void;

    /**
     * Handle incoming WebSocket message.
     */
    public function onMessage(mixed $connection, mixed $data): void;

    /**
     * Handle WebSocket connection close.
     */
    public function onClose(mixed $connection): void;
}
