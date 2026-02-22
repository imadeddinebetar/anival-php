<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;
use Core\Http\Routing\Router;

/**
 * Route Cache Command
 *
 * Caches the compiled route table for improved performance.
 * @internal
 */
class RouteCacheCommand extends Command
{
    protected string $name = 'route:cache';
    protected string $description = 'Create a cache file for routes';
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
        echo "Caching routes...\n";

        // Load route files
        $router = $this->app->get(Router::class);

        // Load web routes
        $webRoutesPath = $this->app->basePath('routes/web.php');
        if (file_exists($webRoutesPath)) {
            require $webRoutesPath;
        }

        // Load API routes
        $apiRoutesPath = $this->app->basePath('routes/api.php');
        if (file_exists($apiRoutesPath)) {
            require $apiRoutesPath;
        }

        // Get all routes
        $routes = $router->getRoutes();

        // Convert routes to serializable format
        $routeData = [];
        foreach ($routes as $route) {
            $routeData[] = [
                'method' => $route->getMethod(),
                'uri' => $route->getUri(),
                'action' => is_string($route->getAction()) ? $route->getAction() : null,
                'name' => $route->getName(),
                'middleware' => $route->getMiddleware(),
            ];
        }

        // Cache the routes
        $cachePath = $this->app->storagePath('framework/routes.php');
        $cacheDir = dirname($cachePath);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $data = array_merge($routeData, [
            '__app_key' => $this->app->get('env'),
            '__cached_at' => time(),
        ]);

        $content = '<?php' . PHP_EOL . 'return ' . var_export($data, true) . ';' . PHP_EOL;

        if (file_put_contents($cachePath, $content) === false) {
            echo "Failed to write route cache.\n";
            return 1;
        }

        echo "Routes cached successfully! (" . count($routes) . " routes)\n";

        return 0;
    }
}
