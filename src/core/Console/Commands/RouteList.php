<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;
use Core\Http\Routing\Router;

/**
 * @internal
 */
class RouteList extends Command
{
    protected string $name = 'route:list';
    protected string $description = 'List all registered routes';
    protected Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    public function handle(array $args, array $options): int
    {
        $verbose = isset($options['verbose']) || isset($options['v']);

        $router = $this->app->get(Router::class);

        $webRoutesPath = $this->app->basePath('routes/web.php');
        if (file_exists($webRoutesPath)) {
            require $webRoutesPath;
        }

        $apiRoutesPath = $this->app->basePath('routes/api.php');
        if (file_exists($apiRoutesPath)) {
            require $apiRoutesPath;
        }

        $routes = $router->getRoutes();

        if (empty($routes)) {
            $this->info('No routes registered.');
            return 0;
        }

        $headers = ['Method', 'URI', 'Name', 'Controller'];
        if ($verbose) {
            $headers[] = 'Middleware';
        }

        $rows = [];

        foreach ($routes as $route) {
            $row = [
                $route->getMethod(),
                $route->getUri(),
                $route->getName() ?? '-',
                is_string($route->getAction()) ? $route->getAction() : 'Closure',
            ];

            if ($verbose) {
                $middleware = $route->getMiddleware();
                $row[] = !empty($middleware) ? implode(', ', $middleware) : '-';
            }

            $rows[] = $row;
        }

        $this->table($headers, $rows);

        $this->line("\nTotal routes: " . count($routes));

        return 0;
    }
}
