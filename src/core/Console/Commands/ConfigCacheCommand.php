<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * @internal
 */
class ConfigCacheCommand extends Command
{
    protected string $name = 'config:cache';
    protected string $description = 'Create a cache file for faster configuration loading';

    public function __construct(private readonly Application $app)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $this->app->cacheConfig();
        $this->info('Configuration cached successfully!');

        return 0;
    }
}
