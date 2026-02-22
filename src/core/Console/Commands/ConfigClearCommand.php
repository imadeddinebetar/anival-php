<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * @internal
 */
class ConfigClearCommand extends Command
{
    protected string $name = 'config:clear';
    protected string $description = 'Remove the configuration cache file';

    public function __construct(private readonly Application $app)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $cachePath = $this->app->storagePath('framework/config.php');

        if (!file_exists($cachePath)) {
            $this->line('No configuration cache found to clear.');
            return 0;
        }

        unlink($cachePath);
        $this->info('Configuration cache cleared!');

        return 0;
    }
}
