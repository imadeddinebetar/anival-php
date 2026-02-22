<?php

namespace Bootstrap\Providers;

use Core\Database\Contracts\DatabaseManagerInterface;
use Core\Database\Internal\DatabaseManager;
use Core\Database\Internal\EloquentAdapter;
use Core\Database\Internal\ModelBinder;
use Core\Database\Contracts\ModelBinderInterface;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EloquentAdapter::class, function () {
            return new EloquentAdapter($this->app->get('config'), $this->app);
        });

        $this->app->singleton(DatabaseManager::class, function ($app) {
            return new DatabaseManager($app->get(EloquentAdapter::class)->getCapsule());
        });

        $this->app->singleton(DatabaseManagerInterface::class, function ($app) {
            return $app->get(DatabaseManager::class);
        });
        $this->app->bind('db', DatabaseManagerInterface::class);
        $this->app->bind(ModelBinderInterface::class, ModelBinder::class);
    }

    public function boot(): void
    {
        /** @var \Core\Config\Contracts\ConfigRepositoryInterface $config */
        $config = $this->app->get('config');

        if ($config->get('database.log_queries', false)) {
            $db = $this->app->get('db');

            $db->connection()->listen(function ($query) use ($config) {
                /** @var \Core\Log\Contracts\LogManagerInterface $logger */
                $logger = $this->app->get(\Core\Log\Contracts\LogManagerInterface::class);

                $logger->channel('query')->info($query->sql, [
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            });
        }
    }
}
