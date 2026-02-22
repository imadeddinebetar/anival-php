<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class Up extends Command
{
    protected string $name = 'up';
    protected string $description = 'Bring the application out of maintenance mode';

    public function handle(array $args, array $options): int
    {
        $downFile = dirname(__DIR__, 3) . '/storage/framework/down';

        if (!file_exists($downFile)) {
            $this->info('Application is not in maintenance mode.');
            return 0;
        }

        unlink($downFile);

        $this->info('Application is now live!');

        return 0;
    }
}
