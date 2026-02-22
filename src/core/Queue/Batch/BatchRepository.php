<?php

namespace Core\Queue\Batch;

/**
 * @internal
 */
interface BatchRepository
{
    public function get($batchId);
    public function find(string $batchId): ?Batch;
    public function store(PendingBatch $batch): Batch;
    public function incrementTotalJobs(string $batchId, int $amount);
    public function decrementPendingJobs(string $batchId, string $jobId);
    public function markAsFinished(string $batchId);
    public function cancel(string $batchId);
    public function transaction(callable $callback);
    public function recordFailure(string $batchId, string $jobId, \Throwable $e);
}
