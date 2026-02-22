<?php

namespace Bootstrap\Providers;

use Core\Queue\Internal\Queue;
use Core\Queue\Internal\SyncQueue;
use Core\Queue\Contracts\QueueInterface;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Queue::class, function () {
            $driver = config('queue.default', 'redis');

            if ($driver === 'sync') {
                $logger = $this->app->has('logger') ? $this->app->get('logger') : null;
                return new SyncQueue($logger, $this->app);
            }

            $redis = $this->createRedisConnection();
            $logger = $this->app->has('logger') ? $this->app->get('logger') : null;
            $maxAttempts = config('queue.max_attempts', 3);
            return new Queue($redis, $logger, $this->app, $maxAttempts);
        });

        $this->app->bind(QueueInterface::class, Queue::class);
        $this->app->bind('queue', QueueInterface::class);

        $this->app->singleton(\Core\Queue\Batch\BatchRepository::class, function () {
            $redis = $this->createRedisConnection();
            return new \Core\Queue\Batch\RedisBatchRepository($redis);
        });
    }

    protected function createRedisConnection(): object
    {
        $host = env('REDIS_HOST', '127.0.0.1');
        $port = (int)env('REDIS_PORT', 6379);
        $user = env('REDIS_USERNAME');
        $password = env('REDIS_PASSWORD');

        if (class_exists('Redis')) {
            $redis = new \Redis();
        } else {
            throw new \RuntimeException('Redis extension is not available. Install ext-redis or use queue.default=sync.');
        }

        if ($redis instanceof \Redis || method_exists($redis, 'connect')) {
            $redis->connect((string)$host, $port);
        }

        if ($password) {
            if ($user) {
                $redis->auth(['user' => $user, 'pass' => $password]);
            } else {
                $redis->auth($password);
            }
        }

        return $redis;
    }
}
