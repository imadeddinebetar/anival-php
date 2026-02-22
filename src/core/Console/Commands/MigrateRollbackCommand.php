<?php

namespace Core\Console\Commands;

use Core\Database\Internal\Migration;
use Core\Database\Internal\MigrationRepository;

/**
 * @internal
 */
class MigrateRollbackCommand extends Command
{
    protected string $name = 'migrate:rollback';
    protected string $description = 'Rollback the last migration batch';

    public function __construct(private readonly MigrationRepository $repository)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $this->repository->ensureTableExists();
        $lastBatch = $this->repository->getLastBatchNumber();

        if ($lastBatch === 0) {
            $this->line('Nothing to rollback.');
            return 0;
        }

        foreach ($this->repository->getMigrationsForBatch($lastBatch) as $migration) {
            $file = dirname(__DIR__, 3) . '/database/migrations/' . $migration->migration . '.php';

            if (!file_exists($file)) {
                continue;
            }

            $this->rollbackMigration($file, $migration->migration);
        }

        $this->info("Rolled back batch {$lastBatch}.");

        return 0;
    }

    private function rollbackMigration(string $file, string $name): void
    {
        $this->line("Rolling back: {$name}");

        $migration = require $file;

        if ($migration instanceof Migration) {
            $migration->down();
        }

        $this->repository->delete($name);
    }
}
