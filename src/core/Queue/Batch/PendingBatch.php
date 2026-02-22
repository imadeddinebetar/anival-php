<?php

namespace Core\Queue\Batch;

use Core\Container\Internal\Application;
use Core\Queue\Internal\Queue;

/**
 * @internal
 */
class PendingBatch
{
    protected Application $app;
    public string $name = '';
    public array $jobs = [];
    public array $options = [];

    public $thenCallback;
    public $catchCallback;
    public $finallyCallback;

    public function __construct(Application $app, array $jobs)
    {
        $this->app = $app;
        $this->jobs = $jobs;
    }

    public function then(callable $callback): self
    {
        $this->thenCallback = $callback;
        return $this;
    }

    public function catch(callable $callback): self
    {
        $this->catchCallback = $callback;
        return $this;
    }

    public function finally(callable $callback): self
    {
        $this->finallyCallback = $callback;
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function onQueue(string $queue): self
    {
        $this->options['queue'] = $queue;
        return $this;
    }

    public function onConnection(string $connection): self
    {
        $this->options['connection'] = $connection;
        return $this;
    }

    public function dispatch(): Batch
    {
        $repository = $this->app->get(BatchRepository::class);
        $queue = $this->app->get(Queue::class);

        $batch = $repository->store($this);

        foreach ($this->jobs as $job) {
            if (is_object($job) && method_exists($job, 'withBatchId')) {
                $job->withBatchId($batch->id);
            }

            // For arrays or objects without trait, we might need a wrapper or metadata in push
            // We'll assume job objects have the trait or we pass batch_id in options

            $queueName = $this->options['queue'] ?? 'default';
            $queue->push(get_class($job), (array)$job, $queueName, ['batch_id' => $batch->id]);
        }

        return $batch;
    }
}
