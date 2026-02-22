<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * @internal
 */
class StorageUnlinkCommand extends Command
{
    protected string $name = 'storage:unlink';
    protected string $description = 'Remove the public/storage symbolic link';

    public function __construct(private readonly Application $app)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $link = $this->app->basePath('public/storage');

        if (!is_link($link)) {
            $this->line('The [public/storage] link does not exist.');
            return 0;
        }

        if (!unlink($link)) {
            $this->error('Error: Failed to remove symbolic link.');
            return 1;
        }

        $this->info('The [public/storage] link has been removed.');

        return 0;
    }
}
