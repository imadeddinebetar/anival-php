<?php

namespace Core\Queue\Traits;

/**
 * Provides a static dispatch() method for jobs.
 *
 * Usage:
 *   SendWelcomeEmail::dispatch(['user_id' => 1]);
 */
trait Dispatchable
{
    /**
     * Dispatch the job with the given data.
     *
     * @param array<string, mixed> $data
     */
    public static function dispatch(array $data = []): void
    {
        $queue = queue();
        $queue->push(static::class, $data);
    }

    /**
     * Dispatch the job with a delay (in seconds).
     *
     * @param int $delay
     * @param array<string, mixed> $data
     */
    public static function dispatchLater(int $delay, array $data = []): void
    {
        $queue = queue();
        $queue->later($delay, static::class, $data);
    }

    /**
     * Dispatch the job on a specific queue.
     *
     * @param string $queueName
     * @param array<string, mixed> $data
     */
    public static function dispatchOn(string $queueName, array $data = []): void
    {
        $queue = queue();
        $queue->push(static::class, $data, $queueName);
    }
}
