<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * @internal
 */
class StorageLinkCommand extends Command
{
    protected string $name = 'storage:link';
    protected string $description = 'Create a symbolic link from public/storage to storage/app/public';

    public function __construct(private readonly Application $app)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $absoluteTarget = $this->app->basePath('storage/app/public');
        $target = '../storage/app/public';
        $link = $this->app->basePath('public/storage');

        if (!is_dir($absoluteTarget)) {
            mkdir($absoluteTarget, 0775, true);
        }

        if (is_link($link)) {
            $this->line('The [public/storage] link already exists.');
            return 0;
        }

        if (file_exists($link)) {
            $this->error('Error: [public/storage] already exists and is not a symbolic link.');
            return 1;
        }

        if (!symlink($target, $link)) {
            $this->error('Error: Failed to create symbolic link.');
            return 1;
        }

        $this->info('The [public/storage] directory has been linked.');

        return 0;
    }
}
