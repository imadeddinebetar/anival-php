<?php

namespace Core\Queue\Batch;

/**
 * @internal
 */
class Batch
{
    public string $id;
    public string $name;
    public int $totalJobs;
    public int $pendingJobs;
    public int $failedJobs;
    public array $failedJobIds;
    public array $options;
    public $createdAt;
    public $cancelledAt;
    public $finishedAt;

    // Callbacks serialized
    public ?string $thenCallback = null;
    public ?string $catchCallback = null;
    public ?string $finallyCallback = null;

    protected BatchRepository $repository;

    public function __construct(BatchRepository $repository, string $id, string $name, int $totalJobs, int $pendingJobs, int $failedJobs, array $failedJobIds, array $options, $createdAt, $cancelledAt = null, $finishedAt = null)
    {
        $this->repository = $repository;
        $this->id = $id;
        $this->name = $name;
        $this->totalJobs = $totalJobs;
        $this->pendingJobs = $pendingJobs;
        $this->failedJobs = $failedJobs;
        $this->failedJobIds = $failedJobIds;
        $this->options = $options;
        $this->createdAt = $createdAt;
        $this->cancelledAt = $cancelledAt;
        $this->finishedAt = $finishedAt;
    }

    public function finished(): bool
    {
        return !is_null($this->finishedAt);
    }

    public function cancelled(): bool
    {
        return !is_null($this->cancelledAt);
    }

    public function cancel(): void
    {
        $this->repository->cancel($this->id);
    }

    public function hasFailures(): bool
    {
        return $this->failedJobs > 0;
    }
}
