<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class TestCommand extends Command
{
    protected string $name = 'test';
    protected string $description = 'Run the application test suite';

    public function handle(array $args, array $options): int
    {
        $command = 'php vendor/bin/phpunit';

        if (!empty($args)) {
            $command .= ' ' . implode(' ', array_map('escapeshellarg', $args));
        }

        $filter = $options['filter'] ?? null;
        if ($filter) {
            $command .= ' --filter ' . escapeshellarg((string) $filter);
        }

        passthru($command, $exitCode);

        return $exitCode;
    }
}
