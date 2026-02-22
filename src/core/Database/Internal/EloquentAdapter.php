<?php

namespace Core\Database\Internal;

use Illuminate\Database\Capsule\Manager as Capsule;
use Core\Config\Contracts\ConfigRepositoryInterface;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\Facade;
use Core\Container\Contracts\ContainerInterface;

/**
 * @internal
 */
class EloquentAdapter
{
    protected Capsule $capsule;
    protected ConfigRepositoryInterface $config;
    protected ?ContainerInterface $container;

    public function __construct(ConfigRepositoryInterface $config, ?ContainerInterface $container = null)
    {
        $this->config = $config;
        $this->container = $container;
        $this->capsule = new Capsule();
        $this->setup();
    }

    protected function setup(): void
    {
        $dbConfig = $this->config->get('database.connections.' . $this->config->get('database.default', 'mysql'), []);

        $this->capsule->addConnection($dbConfig);
        $this->capsule->setAsGlobal();

        if ($this->container && $this->container->has('events')) {
            $events = $this->container->get('events');
            if ($events instanceof Dispatcher) {
                $this->capsule->setEventDispatcher($events);
            }
        }

        // Setup internal container for Capsule with Facade support
        // (isolated here per architecture guidelines — facades are only
        // exposed to Eloquent internals that require them, e.g. 'hashed' cast)
        $illuminateContainer = new Container;
        $illuminateContainer['config'] = new class([
            'hashing.driver' => 'bcrypt',
            'hashing.bcrypt' => ['rounds' => (int) $this->config->get('hashing.bcrypt.rounds', 12)],
        ]) implements \ArrayAccess {
            private array $items;
            public function __construct(array $items)
            {
                $this->items = $items;
            }
            public function get(string $key, $default = null)
            {
                return $this->items[$key] ?? $default;
            }
            public function offsetExists(mixed $offset): bool
            {
                return isset($this->items[$offset]);
            }
            public function offsetGet(mixed $offset): mixed
            {
                return $this->items[$offset] ?? null;
            }
            public function offsetSet(mixed $offset, mixed $value): void
            {
                $this->items[$offset] = $value;
            }
            public function offsetUnset(mixed $offset): void
            {
                unset($this->items[$offset]);
            }
        };
        $illuminateContainer->singleton('hash', function ($app) {
            return new HashManager($app);
        });
        $illuminateContainer->singleton('hash.driver', function ($app) {
            return $app['hash']->driver();
        });
        $capsule = $this->capsule;
        $illuminateContainer->singleton('db.schema', function () use ($capsule) {
            return $capsule->schema();
        });

        $this->capsule->setContainer($illuminateContainer);
        Facade::setFacadeApplication($illuminateContainer);

        $this->capsule->bootEloquent();
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }
}
