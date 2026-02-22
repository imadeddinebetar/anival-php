<?php

namespace Core\WebSocket\Internal;

use Redis;

/**
 * @internal
 */
class RedisAdapter implements RedisInterface
{
    protected ?Redis $redis = null;

    public function __construct()
    {
        if (class_exists(Redis::class)) {
            $this->redis = new Redis();
        }
    }

    public function connect(string $host, int $port, float $timeout = 0.0, $reserved = null, int $retry_interval = 0, float $read_timeout = 0.0): bool
    {
        return $this->redis ? $this->redis->connect($host, $port, $timeout, $reserved, $retry_interval, $read_timeout) : false;
    }

    public function auth($credentials): bool
    {
        return $this->redis ? $this->redis->auth($credentials) : false;
    }

    public function hSet(string $key, string $hashKey, string $value)
    {
        return $this->redis ? $this->redis->hSet($key, $hashKey, $value) : false;
    }

    public function hGet(string $key, string $hashKey)
    {
        return $this->redis ? $this->redis->hGet($key, $hashKey) : false;
    }

    public function hDel(string $key, string $hashKey)
    {
        return $this->redis ? $this->redis->hDel($key, $hashKey) : false;
    }

    public function hGetAll(string $key)
    {
        return $this->redis ? $this->redis->hGetAll($key) : [];
    }

    public function sAdd(string $key, ...$values)
    {
        return $this->redis ? $this->redis->sAdd($key, ...$values) : false;
    }

    public function sRem(string $key, ...$values)
    {
        return $this->redis ? $this->redis->sRem($key, ...$values) : false;
    }

    public function sCard(string $key)
    {
        return $this->redis ? $this->redis->sCard($key) : 0;
    }

    public function sMembers(string $key)
    {
        return $this->redis ? $this->redis->sMembers($key) : [];
    }

    public function lPush(string $key, ...$values)
    {
        return $this->redis ? $this->redis->lPush($key, ...$values) : false;
    }

    public function rPush(string $key, ...$values)
    {
        return $this->redis ? $this->redis->rPush($key, ...$values) : false;
    }

    public function lTrim(string $key, int $start, int $stop)
    {
        return $this->redis ? $this->redis->lTrim($key, $start, $stop) : false;
    }

    public function lRange(string $key, int $start, int $end)
    {
        return $this->redis ? $this->redis->lRange($key, $start, $end) : [];
    }

    public function lIndex(string $key, int $index)
    {
        return $this->redis ? $this->redis->lIndex($key, $index) : false;
    }

    public function incr(string $key)
    {
        return $this->redis ? $this->redis->incr($key) : false;
    }

    public function expire(string $key, int $ttl)
    {
        return $this->redis ? $this->redis->expire($key, $ttl) : false;
    }
}
