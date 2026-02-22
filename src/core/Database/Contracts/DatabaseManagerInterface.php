<?php

namespace Core\Database\Contracts;

interface DatabaseManagerInterface
{
    public function connection(?string $name = null): mixed;
    public function table(string $table, ?string $connection = null): mixed;
    public function schema(?string $connection = null): mixed;
    public function transaction(\Closure $callback, int $attempts = 1): mixed;
}
