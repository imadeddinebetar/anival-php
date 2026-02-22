<?php

namespace Core\Queue\Batch;

use Core\Queue\Internal\Queue;

/**
 * @internal
 */
class RedisBatchRepository implements BatchRepository
{
    protected $redis; // Using mixed type for resilience
    protected int $ttl = 43200; // 12 hours

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function get($batchId)
    {
        return $this->find($batchId);
    }

    public function find(string $batchId): ?Batch
    {
        $data = $this->redis->hGetAll("batch:{$batchId}");

        if (empty($data)) {
            return null;
        }

        return $this->toBatch($batchId, $data);
    }

    public function store(PendingBatch $batch): Batch
    {
        $id = uniqid('batch_');

        $this->redis->hSet("batch:{$id}", 'id', $id);
        $this->redis->hSet("batch:{$id}", 'name', $batch->name);
        $this->redis->hSet("batch:{$id}", 'total_jobs', count($batch->jobs));
        $this->redis->hSet("batch:{$id}", 'pending_jobs', count($batch->jobs));
        $this->redis->hSet("batch:{$id}", 'failed_jobs', 0);
        $this->redis->hSet("batch:{$id}", 'created_at', time());

        // Serialize callbacks if possible (Closure serialization is hard directly, usually use opis/closure or similar)
        // For this implementation, we might skip full serialization of closures if not supported
        // Or assume they are static class methods or serializeable
        // For simplicity now, let's skip complex closure storage and focus on counters

        $this->redis->expire("batch:{$id}", $this->ttl);

        return $this->find($id);
    }

    public function incrementTotalJobs(string $batchId, int $amount)
    {
        $this->redis->hIncrBy("batch:{$batchId}", 'total_jobs', $amount);
        $this->redis->hIncrBy("batch:{$batchId}", 'pending_jobs', $amount);
    }

    public function decrementPendingJobs(string $batchId, string $jobId)
    {
        $pending = $this->redis->hIncrBy("batch:{$batchId}", 'pending_jobs', -1);

        if ($pending === 0) {
            $this->markAsFinished($batchId);
        }

        return $pending;
    }

    public function markAsFinished(string $batchId)
    {
        $this->redis->hSet("batch:{$batchId}", 'finished_at', time());
    }

    public function cancel(string $batchId)
    {
        $this->redis->hSet("batch:{$batchId}", 'cancelled_at', time());
    }

    public function transaction(callable $callback)
    {
        return $callback();
    }

    public function recordFailure(string $batchId, string $jobId, \Throwable $e)
    {
        $this->redis->hIncrBy("batch:{$batchId}", 'failed_jobs', 1);
        $this->redis->rPush("batch:{$batchId}:failed_ids", $jobId);
    }

    protected function toBatch($id, $data): Batch
    {
        return new Batch(
            $this,
            $id,
            $data['name'] ?? '',
            (int)($data['total_jobs'] ?? 0),
            (int)($data['pending_jobs'] ?? 0),
            (int)($data['failed_jobs'] ?? 0),
            [], // Failed IDs loaded separately if needed
            [],
            $data['created_at'] ?? null,
            $data['cancelled_at'] ?? null,
            $data['finished_at'] ?? null
        );
    }
}
