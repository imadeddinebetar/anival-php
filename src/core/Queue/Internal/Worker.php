<?php

namespace Core\Queue\Internal;

use Core\Container\Internal\Application;
use Core\Events\Contracts\EventDispatcherInterface;
use Core\Queue\Internal\Events as Events;

/**
 * @internal
 */
class Worker
{
    protected Queue $queue;
    protected EventDispatcherInterface $events;
    protected array $config;
    protected bool $shouldQuit = false;
    protected Application $app;

    public function __construct(Application $app, array $config = [])
    {
        $this->app = $app;
        $this->config = array_merge([
            'sleep' => 3,
            'tries' => 3,
            'timeout' => 60,
            'memory' => 128,
        ], $config);

        $this->queue = $app->get(Queue::class);
        $this->events = $app->get(EventDispatcherInterface::class);

        // Register PCNTL signals only in CLI context, outside of test runs
        if (php_sapi_name() === 'cli' && !defined('PHPUNIT_COMPOSER_INSTALL')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
        }
    }

    public function work(string $connection = 'default', string $queue = 'default'): void
    {
        $queues = explode(',', $queue);

        echo "Worker started for queues: " . implode(', ', $queues) . "\n";
        echo "PID: " . getmypid() . "\n";
        echo "Memory limit: {$this->config['memory']}MB\n";
        echo str_repeat('-', 50) . "\n";

        while (!$this->shouldQuit) {
            // Check memory usage
            if ($this->memoryExceeded()) {
                echo "Memory limit exceeded, stopping worker\n";
                break;
            }

            $jobProcessed = false;

            foreach ($queues as $queueName) {
                // Process next job
                $job = $this->queue->pop(trim($queueName));

                if ($job) {
                    $this->processJob($job, trim($queueName));
                    $jobProcessed = true;
                    // Keep processing high priority queue until empty?
                    // For now, let's just process one and loop to check again strictly in order
                    break;
                }
            }

            if (!$jobProcessed) {
                // No jobs available in any queue, sleep
                sleep($this->config['sleep']);
            }
        }

        echo "Worker stopped gracefully\n";
    }

    public function processJob(array $job, string $queueName): void
    {
        $jobClass = $job['job'];
        $jobData = $job['data'];
        $jobId = $job['id'];
        $quiet = $this->config['quiet'] ?? false;

        if (!$quiet) {
            echo "[" . date('Y-m-d H:i:s') . "] Processing: {$jobClass} (ID: {$jobId})\n";
        }

        $this->events->dispatch(Events\JobProcessing::class, new Events\JobProcessing($job));

        $startTime = microtime(true);

        try {
            // Set timeout
            set_time_limit($this->config['timeout']);

            // Execute job
            if (class_exists($jobClass)) {
                $instance = new $jobClass();

                // Get middleware if defined
                $middleware = [];
                if (method_exists($instance, 'middleware')) {
                    $middleware = $instance->middleware();
                }

                // Run through pipeline
                (new \Core\Queue\Internal\Pipeline($this->app))
                    ->send($instance)
                    ->through($middleware)
                    ->then(function ($instance) use ($jobData) {
                        if (method_exists($instance, 'handle')) {
                            $instance->handle($jobData);
                        } else {
                            throw new \Exception("Job class " . get_class($instance) . " does not have a handle method");
                        }
                    });
            } else {
                throw new \Exception("Job class {$jobClass} not found");
            }

            // Mark as completed
            $this->queue->complete($job, $queueName);

            // Handle batch update
            if (isset($job['batch_id'])) {
                $batchRepository = $this->app->get(\Core\Queue\Batch\BatchRepository::class);
                $batchRepository->decrementPendingJobs($job['batch_id'], $jobId);
            }

            $this->events->dispatch(Events\JobProcessed::class, new Events\JobProcessed($job));

            $duration = round((microtime(true) - $startTime) * 1000, 2);
            if (!$quiet) {
                echo "[" . date('Y-m-d H:i:s') . "] Completed: {$jobClass} ({$duration}ms)\n";
                echo str_repeat('-', 50) . "\n";
            }
        } catch (\Exception $e) {
            $this->queue->fail($job, $e, $queueName);

            // Handle batch failure
            if (isset($job['batch_id'])) {
                $batchRepository = $this->app->get(\Core\Queue\Batch\BatchRepository::class);
                $batchRepository->recordFailure($job['batch_id'], $jobId, $e);
            }

            $this->events->dispatch(Events\JobFailed::class, new Events\JobFailed($job, $e));

            if (!$quiet) {
                echo "[" . date('Y-m-d H:i:s') . "] Failed: {$jobClass}\n";
                echo "Error: {$e->getMessage()}\n";
                // Only echo trace if specifically requested or not in quiet mode
                if ($this->config['verbose'] ?? false) {
                    echo "Trace: {$e->getTraceAsString()}\n";
                }
                echo str_repeat('-', 50) . "\n";
            }
        }
    }

    protected function memoryExceeded(): bool
    {
        $limit = $this->config['memory'] * 1024 * 1024; // Convert MB to bytes
        $usage = memory_get_usage(true);

        return $usage >= $limit;
    }

    public function handleSignal(int $signal): void
    {
        echo "\nReceived signal: {$signal}\n";
        $this->shouldQuit = true;
    }
}
