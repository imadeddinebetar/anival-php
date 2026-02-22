<?php

namespace Core\Container\Internal;

use DI\Container;
use DI\ContainerBuilder;
use Core\Container\Contracts\ContainerInterface;

/**
 * @internal
 */
class Application implements ContainerInterface
{
    protected static ?Application $instance = null;
    protected Container $container;
    /** @var array<int, object> */
    protected array $providers = [];
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = $basePath;
        $this->setupContainer();
        static::$instance = $this;
    }

    public static function getInstance(): ?static
    {
        return static::$instance;
    }

    protected function setupContainer(): void
    {
        $builder = new ContainerBuilder();
        $builder->useAttributes(true);
        $builder->useAutowiring(true);

        // Add base bindings as definitions
        $builder->addDefinitions([
            'app' => $this,
            static::class => $this,
            'path.base' => $this->basePath,
            'path.config' => $this->basePath . DIRECTORY_SEPARATOR . 'config',
            'path.storage' => $this->basePath . DIRECTORY_SEPARATOR . 'storage',
            'path.public' => $this->basePath . DIRECTORY_SEPARATOR . 'public',
            'path.resources' => $this->basePath . DIRECTORY_SEPARATOR . 'resources',
            'response.factory' => \DI\create(\Nyholm\Psr7\Factory\Psr17Factory::class),
            \Psr\Http\Message\ResponseFactoryInterface::class => \DI\get('response.factory'),
            'logger' => \DI\create(\Psr\Log\NullLogger::class),
            \Core\Http\Routing\Contracts\RouteDispatcherInterface::class => \DI\autowire(\Core\Http\Routing\Internal\RouteDispatcher::class),
            \Core\Http\Routing\Router::class => \DI\autowire(\Core\Http\Routing\Router::class),

            'router' => \DI\get(\Core\Http\Routing\Router::class),
        ]);

        $this->container = $builder->build();

        // Register the container itself
        $this->container->set('container', $this->container);
        $this->container->set(\Core\Container\Contracts\ContainerInterface::class, $this);

        // Register the environment binding
        $this->container->set('env', $_ENV['APP_ENV'] ?? 'production');
    }

    public function register(string|object $provider): void
    {
        if (is_string($provider)) {
            $instance = new $provider($this);
        } else {
            $instance = $provider;
        }

        $instance->register();

        $this->providers[] = $instance;
    }

    /**
     * Register all of the configured service providers.
     */
    public function registerConfiguredProviders(): void
    {
        $providers = config('app.providers', []);

        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Boot the application's service providers.
     */
    public function boot(): void
    {
        // Sort providers by priority (higher is earlier)
        usort($this->providers, function ($a, $b) {
            $priorityA = property_exists($a, 'priority') ? $a->priority : 0;
            $priorityB = property_exists($b, 'priority') ? $b->priority : 0;
            return $priorityB <=> $priorityA;
        });

        foreach ($this->providers as $provider) {
            $provider->boot();
        }
    }

    /**
     * Bootstrap the application.
     */
    public function bootstrap(): void
    {
        $this->loadConfiguration();
        $this->registerConfiguredProviders();
        $this->boot();
    }

    /**
     * Load the application configuration.
     */
    public function loadConfiguration(): void
    {
        if ($this->container->has(\Core\Config\Contracts\ConfigRepositoryInterface::class)) {
            return;
        }

        // Check for cached configuration first
        $cachePath = $this->storagePath('framework/config.php');
        if (file_exists($cachePath)) {
            $config = require $cachePath;
            $this->container->set(\Core\Config\Contracts\ConfigRepositoryInterface::class, new \Core\Config\Internal\ConfigRepository($config));
            $this->container->set('config', \DI\get(\Core\Config\Contracts\ConfigRepositoryInterface::class));
            return;
        }

        $configPath = $this->configPath();
        $files = glob($configPath . '/*.php');
        $config = [];

        foreach ($files as $file) {
            $name = basename($file, '.php');
            $config[$name] = require $file;
        }

        $this->container->set(\Core\Config\Contracts\ConfigRepositoryInterface::class, new \Core\Config\Internal\ConfigRepository($config));
        $this->container->set('config', \DI\get(\Core\Config\Contracts\ConfigRepositoryInterface::class));
    }


    public function get(string $id): mixed
    {
        return $this->container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Register a singleton binding in the container.
     *
     * Singletons are resolved once and the same instance is returned for all subsequent resolutions.
     */
    public function singleton(string $id, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }
        if (is_string($concrete) && $concrete !== $id && $this->container->has($concrete)) {
            $this->container->set($id, \DI\get($concrete));
            return;
        }
        if (is_callable($concrete)) {
            $this->container->set($id, \DI\factory($concrete));
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $this->container->set($id, \DI\autowire($concrete));
        } else {
            $this->container->set($id, $concrete);
        }
    }

    /**
     * Register a binding with the container.
     *
     * Bindings create a new instance each time they are resolved (non-shared).
     * For aliases (string $concrete !== $id), lazy resolution via \DI\get is used.
     */
    public function bind(string $id, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $id;
        }

        if (is_string($concrete) && $concrete !== $id) {
            // Alias: resolve via DI\get lazy reference
            $this->container->set($id, \DI\get($concrete));
            return;
        }

        if (is_callable($concrete)) {
            // Factory closure: wrap so it creates a new instance each call
            $this->container->set($id, \DI\factory($concrete));
        } elseif (is_string($concrete) && class_exists($concrete)) {
            // Class binding: create a new instance per resolution
            $className = $concrete;
            $this->container->set($id, \DI\factory(function (\DI\Container $c) use ($className) {
                return $c->make($className);
            }));
        } else {
            $this->container->set($id, $concrete);
        }
    }

    /**
     * Validate that the required environment variables are set.
     *
     * @param array<int, string> $required
     * @throws \RuntimeException
     */
    public function validateEnvironment(array $required): void
    {
        foreach ($required as $key) {
            if (env($key) === null) {
                throw new \RuntimeException("Missing required environment variable: {$key}");
            }
        }
    }

    /**
     * Cache the application configuration.
     */
    public function cacheConfig(): void
    {
        $this->loadConfiguration();
        $config = $this->container->get('config')->all();

        $cachePath = $this->storagePath('framework/config.php');

        $cacheDir = dirname($cachePath);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        $content = '<?php return ' . var_export($config, true) . ';' . PHP_EOL;
        file_put_contents($cachePath, $content);
    }

    public function basePath(string $path = ''): string
    {
        $path = $path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '';
        return $this->basePath . $path;
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }

    public function make(string $id, array $parameters = []): mixed
    {
        return $this->container->make($id, $parameters);
    }

    public function call(callable|array|string $callable, array $parameters = []): mixed
    {
        return $this->container->call($callable, $parameters);
    }

    public function environment(): string
    {
        return env('APP_ENV', 'production');
    }

    public function storagePath(string $path = ''): string
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}
