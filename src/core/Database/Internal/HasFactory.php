<?php

namespace Core\Database\Internal;

/**
 * @internal
 */
trait HasFactory
{
    /**
     * Get a new factory instance for the model.
     *
     * @param  int|null  $count
     * @param  array<string, mixed>  $state
     * @return \Core\Database\Internal\Factory
     */
    public static function factory($count = null, $state = [])
    {
        $factory = static::newFactory();

        if (! $factory) {
            $modelName = class_basename(static::class);
            $factoryName = "Database\\Factories\\{$modelName}Factory";
            if (class_exists($factoryName)) {
                $factory = $factoryName::new();
            } else {
                // Fallback to basic factory if specific factory class doesn't exist?
                // For now, assume it must exist or autoloader finds it.
                // However, since we haven't run composer dump-autoload yet, class_exists might return false.
                // We will rely on manual require in tests/bootstrap if needed, but hopefully autoloader works.
                if (!class_exists($factoryName)) {
                    throw new \RuntimeException("Factory class [{$factoryName}] not found for model [" . static::class . "]");
                }
                $factory = $factoryName::new();
            }
        }

        if ($count !== null) {
            $factory = $factory->count($count);
        }

        if (! empty($state)) {
            $factory = $factory->state($state);
        }

        return $factory;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Core\Database\Internal\Factory|null
     */
    protected static function newFactory()
    {
        return null;
    }
}
