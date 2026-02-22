<?php

namespace Core\Database\Internal;

use Illuminate\Database\Capsule\Manager as Capsule;
use Core\Database\Contracts\DatabaseManagerInterface;

/**
 * @internal
 */
class DatabaseManager implements DatabaseManagerInterface
{
    protected Capsule $capsule;

    public function __construct(Capsule $capsule)
    {
        $this->capsule = $capsule;
    }

    /** @return \Illuminate\Database\Query\Builder */
    public function table(string $table, ?string $connection = null): mixed
    {
        return $this->capsule->getConnection($connection)->table($table);
    }

    /** @return \Illuminate\Database\Connection */
    public function connection(?string $name = null): mixed
    {
        return $this->capsule->getConnection($name);
    }

    /**
     * Execute a closure within a database transaction.
     *
     * @param  \Closure  $callback
     * @param  int  $attempts
     * @return mixed
     *
     * @throws \Throwable
     */
    public function transaction(\Closure $callback, int $attempts = 1): mixed
    {
        return $this->capsule->getConnection()->transaction($callback, $attempts);
    }

    /** @return \Illuminate\Database\Schema\Builder */
    public function schema(?string $connection = null): mixed
    {
        return $this->capsule->getConnection($connection)->getSchemaBuilder();
    }
}
