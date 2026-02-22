<?php

namespace Core\WebSocket\Contracts;

/**
 * Contract for Redis operations used by the WebSocket module.
 */
interface RedisInterface
{
    public function connect(string $host, int $port, float $timeout = 0.0, $reserved = null, int $retry_interval = 0, float $read_timeout = 0.0): bool;
    public function auth($credentials): bool;
    public function hSet(string $key, string $hashKey, string $value);
    public function hGet(string $key, string $hashKey);
    public function hDel(string $key, string $hashKey);
    public function hGetAll(string $key);
    public function sAdd(string $key, ...$values);
    public function sRem(string $key, ...$values);
    public function sCard(string $key);
    public function sMembers(string $key);
    public function lPush(string $key, ...$values);
    public function rPush(string $key, ...$values);
    public function lTrim(string $key, int $start, int $stop);
    public function lRange(string $key, int $start, int $end);
    public function lIndex(string $key, int $index);
    public function incr(string $key);
    public function expire(string $key, int $ttl);
}
