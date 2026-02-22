<?php

namespace Core\Queue\Batch;

/**
 * @internal
 */
trait Batchable
{
    public ?string $batchId = null;

    public function withBatchId(string $batchId): self
    {
        $this->batchId = $batchId;
        return $this;
    }

    public function batch(): ?Batch
    {
        if ($this->batchId) {
            // In a real app we'd resolve the repository from container
            // For now, this is a placeholder.
            // We need a way to resolve repository here.
            // Maybe we just attach the ID and the worker handles logic.
            // But user might want to check batch status inside job.

            // Allow resolving if we have container access or facade access
            // return \Core\Facades\Bus::findBatch($this->batchId);
            return null; // Placeholder
        }
        return null;
    }
}
