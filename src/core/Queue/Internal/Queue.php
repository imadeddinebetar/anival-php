<?php

namespace Core\Queue\Internal;

use Core\Container\Internal\Application;
use Core\Queue\Contracts\QueueInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @internal
 */
class Queue implements QueueInterface
{
    /** @var \Redis|object */
    protected object $redis;
    protected string $defaultQueue = 'default';
    protected LoggerInterface $logger;
    protected int $maxAttempts;

    protected ?Application $container;

    public function __construct(object $redis, ?LoggerInterface $logger = null, ?Application $container = null, int $maxAttempts = 3)
    {
        $this->redis = $redis;
        $this->container = $container;
        $this->maxAttempts = $maxAttempts;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param string $job
     * @param array<string, mixed> $data
     * @param string|null $queue
     * @param array<string, mixed> $options
     */
    public function push(string $job, array $data = [], ?string $queue = null, array $options = []): void
    {
        $queue = $queue ?: $this->defaultQueue;

        $payload = [
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'created_at' => time(),
            'id' => $this->generateId(),
        ];

        if (isset($options['batch_id'])) {
            $payload['batch_id'] = $options['batch_id'];
        }

        $this->redis->rPush("queue:{$queue}", json_encode($payload));

        $this->logger->info("Job pushed to queue '{$queue}': {$job}");
    }

    /** @param array<string, mixed> $data */
    public function later(int $delay, string $job, array $data = [], ?string $queue = null): void
    {
        $queue = $queue ?: $this->defaultQueue;

        $payload = json_encode([
            'job' => $job,
            'data' => $data,
            'attempts' => 0,
            'created_at' => time(),
            'id' => $this->generateId(),
        ]);

        $availableAt = time() + $delay;

        $this->redis->zAdd("queue:{$queue}:delayed", $availableAt, $payload);

        $this->logger->info("Delayed job pushed to queue '{$queue}': {$job} (delay: {$delay}s)");
    }

    /** @return array<string, mixed>|null */
    public function pop(?string $queue = null): ?array
    {
        $queue = $queue ?: $this->defaultQueue;

        $this->migrateDelayedJobs($queue);

        $payload = $this->redis->lPop("queue:{$queue}");

        if (!$payload) {
            return null;
        }

        $job = json_decode($payload, true);

        $this->redis->hSet("queue:{$queue}:processing", $job['id'], $payload);

        return $job;
    }

    /** @param array<string, mixed> $job */
    public function complete(array $job, ?string $queue = null): void
    {
        $queue = $queue ?: $this->defaultQueue;
        $this->redis->hDel("queue:{$queue}:processing", $job['id']);

        $this->logger->info("Job completed: {$job['job']}");
    }

    /** @param array<string, mixed> $job */
    public function fail(array $job, \Exception $e, ?string $queue = null): void
    {
        $queue = $queue ?: $this->defaultQueue;

        $job['attempts']++;
        $job['error'] = $e->getMessage();
        $job['failed_at'] = time();

        if ($job['attempts'] >= $this->maxAttempts) {
            $this->redis->hSet(
                "queue:failed",
                $job['id'],
                json_encode($job)
            );

            $this->redis->hDel("queue:{$queue}:processing", $job['id']);

            $this->logger->error("Job failed permanently: {$job['job']} (attempts: {$job['attempts']})");
        } else {
            $delay = (int) (pow(2, $job['attempts'] - 1) * 60);

            $this->redis->hDel("queue:{$queue}:processing", $job['id']);
            $this->redis->zAdd(
                "queue:{$queue}:delayed",
                time() + $delay,
                json_encode($job)
            );

            $this->logger->warning("Job failed, retrying in {$delay}s: {$job['job']} (attempt {$job['attempts']})");
        }
    }

    public function size(?string $queue = null): int
    {
        $queue = $queue ?: $this->defaultQueue;
        return $this->redis->lLen("queue:{$queue}");
    }

    public function clear(?string $queue = null): void
    {
        $queue = $queue ?: $this->defaultQueue;

        $this->redis->del("queue:{$queue}");
        $this->redis->del("queue:{$queue}:delayed");
        $this->redis->del("queue:{$queue}:processing");

        $this->logger->info("Queue cleared: {$queue}");
    }

    /** @return array<int, array<string, mixed>> */
    public function getFailedJobs(int $limit = 100): array
    {
        $failed = $this->redis->hGetAll("queue:failed");

        $jobs = [];
        foreach ($failed as $id => $payload) {
            $jobs[] = json_decode($payload, true);
        }

        return array_slice($jobs, 0, $limit);
    }

    public function retry(string $jobId, ?string $queue = null): void
    {
        $payload = $this->redis->hGet("queue:failed", $jobId);

        if ($payload) {
            $this->retryPayload($payload, $jobId, $queue);
        }
    }

    public function retryAll(?string $queue = null): void
    {
        $failed = $this->redis->hGetAll("queue:failed");

        foreach ($failed as $jobId => $payload) {
            $this->retryPayload($payload, $jobId, $queue);
        }
    }

    protected function retryPayload(string $payload, string $jobId, ?string $queue = null): void
    {
        $job = json_decode($payload, true);
        $job['attempts'] = 0;
        unset($job['error'], $job['failed_at']);

        // Use original queue if not provided
        // Note: The original implementation didn't store the queue name in the job payload.
        // Ideally we should have stored it. For now, we fallback to default or provided arg.
        $queue = $queue ?: $this->defaultQueue;

        $this->redis->rPush("queue:{$queue}", json_encode($job));
        $this->redis->hDel("queue:failed", $jobId);

        $this->logger->info("Job retried: {$job['job']}");
    }

    protected function migrateDelayedJobs(string $queue): void
    {
        $now = (string) time();

        $jobs = $this->redis->zRangeByScore(
            "queue:{$queue}:delayed",
            '0',
            $now
        );

        foreach ($jobs as $payload) {
            $this->redis->rPush("queue:{$queue}", $payload);
            $this->redis->zRem("queue:{$queue}:delayed", $payload);
        }
    }

    protected function generateId(): string
    {
        return uniqid('job_', true);
    }

    public function setContainer(?Application $container): void
    {
        $this->container = $container;
    }

    public function batch(array $jobs): \Core\Queue\Batch\PendingBatch
    {
        // Assuming container is Application instance or has access to it
        // If container is null, we might need a fallback or fail
        return new \Core\Queue\Batch\PendingBatch($this->container, $jobs);
    }

    /** @return array<string, mixed> */
    public function stats(?string $queue = null): array
    {
        $queue = $queue ?: $this->defaultQueue;

        return [
            'queue' => $queue,
            'pending' => $this->redis->lLen("queue:{$queue}"),
            'delayed' => $this->redis->zCard("queue:{$queue}:delayed"),
            'processing' => $this->redis->hLen("queue:{$queue}:processing"),
            'failed' => $this->redis->hLen("queue:failed"),
        ];
    }
    public function batchRepository(): \Core\Queue\Batch\BatchRepository
    {
        if (!$this->container) {
            throw new \RuntimeException("Container not set on Queue instance");
        }
        return $this->container->get(\Core\Queue\Batch\BatchRepository::class);
    }
}
