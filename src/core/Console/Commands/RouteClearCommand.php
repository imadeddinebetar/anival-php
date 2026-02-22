<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * Clear Route Cache Command
 *
 * Clears the cached route table.
 * @internal
 */
class RouteClearCommand extends Command
{
    protected string $name = 'route:clear';
    protected string $description = 'Remove the route cache file';
    protected Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    /**
     * Execute the command
     */
    public function handle(array $args = [], array $options = []): int
    {
        $cachePath = $this->app->storagePath('framework/routes.php');

        if (!file_exists($cachePath)) {
            echo "No route cache to clear.\n";
            return 0;
        }

        if (unlink($cachePath)) {
            echo "Route cache cleared successfully.\n";
            return 0;
        }

        echo "Failed to clear route cache.\n";
        return 1;
    }
}
