<?php

namespace Core\Queue\Internal;

use Core\Queue\Contracts\QueueInterface;
use Core\Container\Internal\Application;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Synchronous queue driver — executes jobs immediately in-process.
 * Ideal for local development, testing, and queues where async is not needed.
 *
 * @internal
 */
class SyncQueue implements QueueInterface
{
    protected LoggerInterface $logger;
    protected ?Application $container;

    public function __construct(?LoggerInterface $logger = null, ?Application $container = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->container = $container;
    }

    /**
     * @param string $job
     * @param array<string, mixed> $data
     * @param string|null $queue
     * @param array<string, mixed> $options
     */
    public function push(string $job, array $data = [], ?string $queue = null, array $options = []): void
    {
        $this->logger->info("Sync queue: executing {$job} immediately");

        try {
            $instance = $this->resolveJob($job);
            $instance->handle($data);
            $this->logger->info("Sync queue: {$job} completed successfully");
        } catch (\Throwable $e) {
            $this->logger->error("Sync queue: {$job} failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public function later(int $delay, string $job, array $data = [], ?string $queue = null): void
    {
        // Sync driver ignores delay — execute immediately
        $this->push($job, $data, $queue);
    }

    /** @return array<string, mixed>|null */
    public function pop(?string $queue = null): ?array
    {
        // Sync driver has no pending jobs
        return null;
    }

    /** @param array<string, mixed> $job */
    public function complete(array $job, ?string $queue = null): void
    {
        // No-op for sync driver
    }

    /**
     * Resolve the job class instance.
     */
    protected function resolveJob(string $jobClass): object
    {
        if ($this->container && $this->container->has($jobClass)) {
            return $this->container->get($jobClass);
        }

        return new $jobClass();
    }
}
