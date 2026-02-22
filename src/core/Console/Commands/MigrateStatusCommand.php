<?php

namespace Core\Console\Commands;

use Core\Database\Internal\MigrationRepository;

/**
 * @internal
 */
class MigrateStatusCommand extends Command
{
    protected string $name = 'migrate:status';
    protected string $description = 'Show the status of each migration';

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

        $this->line('+--------------------------------------+');
        $this->line('| Migration Status                     |');
        $this->line('+--------------------------------------+');

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $status = in_array($name, $ranMigrations, true) ? '[Ran]' : '[Pending]';
            $this->line("| {$status} {$name}");
        }

        $this->line('+--------------------------------------+');
        $this->line('Ran: ' . count($ranMigrations) . ' | Pending: ' . (count($files) - count($ranMigrations)));

        return 0;
    }
}
