<?php

namespace Core\Queue\Traits;

/**
 * Provides queue configuration properties for jobs.
 */
trait Queueable
{
    /**
     * The name of the queue the job should be sent to.
     */
    public ?string $queue = null;

    /**
     * The number of seconds before the job should be made available.
     */
    public int $delay = 0;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Set the desired queue for the job.
     *
     * @return $this
     */
    public function onQueue(string $queue): static
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * @return $this
     */
    public function withDelay(int $seconds): static
    {
        $this->delay = $seconds;
        return $this;
    }
}
