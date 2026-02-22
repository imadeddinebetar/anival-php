<?php

namespace Core\Http\Routing;

use Core\Container\Internal\Application;

/**
 * Route Cache Manager
 *
 * Serializes and caches the compiled route table to improve
 * route dispatching performance on subsequent requests.
 * @internal
 */
class RouteCache
{
    protected Application $app;
    protected string $cachePath;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->cachePath = $app->storagePath('framework/routes.php');
    }

    /**
     * Get cached routes if available
     */
    public function getCached(): ?array
    {
        if (!$this->isCached()) {
            return null;
        }

        $cached = require $this->cachePath;

        // Verify cache is valid (check app key)
        if (!isset($cached['__app_key']) || $cached['__app_key'] !== $this->app->get('env')) {
            return null;
        }

        // Remove metadata
        unset($cached['__app_key'], $cached['__cached_at']);

        return $cached;
    }

    /**
     * Check if valid cache exists
     */
    public function isCached(): bool
    {
        if (!file_exists($this->cachePath)) {
            return false;
        }

        // Check if cache is expired (24 hours default)
        $cached = @file_get_contents($this->cachePath);
        if ($cached === false) {
            return false; // @codeCoverageIgnore
        }

        // Quick check for expiration
        $expiration = 86400; // 24 hours
        if (preg_match('/__cached_at\'\s*=>\s*(\d+)/', $cached, $matches)) {
            if (time() - (int)$matches[1] > $expiration) {
                return false;
            }
        }

        return true;
    }

    /**
     * Cache the route collection
     */
    public function cache(array $routes): bool
    {
        $cacheDir = dirname($this->cachePath);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        // Add metadata
        $data = array_merge($routes, [
            '__app_key' => $this->app->get('env'),
            '__cached_at' => time(),
        ]);

        $content = '<?php' . PHP_EOL . 'return ' . var_export($data, true) . ';' . PHP_EOL;

        return file_put_contents($this->cachePath, $content) !== false;
    }

    /**
     * Clear the route cache
     */
    public function clear(): bool
    {
        if (file_exists($this->cachePath)) {
            return unlink($this->cachePath);
        }

        return true;
    }

    /**
     * Get the cache file path
     */
    public function getCachePath(): string
    {
        return $this->cachePath;
    }
}
