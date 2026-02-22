<?php

namespace Core\Queue\Contracts;

interface QueueInterface
{
    /** @param array<string, mixed> $data */
    public function push(string $job, array $data = [], ?string $queue = null, array $options = []): void;
    /** @param array<string, mixed> $data */
    public function later(int $delay, string $job, array $data = [], ?string $queue = null): void;
    /** @return array<string, mixed>|null */
    public function pop(?string $queue = null): ?array;
    /** @param array<string, mixed> $job */
    public function complete(array $job, ?string $queue = null): void;
}
