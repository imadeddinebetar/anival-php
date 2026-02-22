<?php

namespace Core\Console\Commands;

use Core\Database\Internal\Migration;
use Core\Database\Internal\MigrationRepository;

/**
 * @internal
 */
class MigrateCommand extends Command
{
    protected string $name = 'migrate';
    protected string $description = 'Run all pending migrations';

    public function __construct(private readonly MigrationRepository $repository)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $this->repository->ensureTableExists();

        $files = glob(dirname(__DIR__, 3) . '/database/migrations/*.php');
        sort($files);

        $ranMigrations = $this->repository->getRanMigrations();
        $batch = $this->repository->getNextBatchNumber();
        $count = 0;

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, $ranMigrations, true)) {
                continue;
            }

            $this->runMigration($file, $name, $batch);
            $count++;
        }

        if ($count === 0) {
            $this->line('Nothing to migrate.');
            return 0;
        }

        $this->info("Successfully ran {$count} migrations.");

        return 0;
    }

    private function runMigration(string $file, string $name, int $batch): void
    {
        $this->line("Migrating: {$name}");

        $migration = require $file;

        if ($migration instanceof Migration) {
            $migration->up();
        }

        $this->repository->log($name, $batch);
    }
}
