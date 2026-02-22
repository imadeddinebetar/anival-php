<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * @internal
 */
class DbSeedCommand extends Command
{
    protected string $name = 'db:seed';
    protected string $description = 'Seed the database with records';

    public function __construct(private readonly Application $app)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $class = $args[0] ?? 'DatabaseSeeder';
        $this->line("Seeding: {$class}");

        $db = $this->app->get('db');

        if (class_exists($class)) {
            (new $class($db))->run();
            $this->info('Database seeding completed successfully.');
            return 0;
        }

        $namespaces = [
            'Database\\Seeders\\',
            'App\\Database\\Seeds\\',
        ];

        foreach ($namespaces as $namespace) {
            $namespacedClass = $namespace . $class;

            if (!class_exists($namespacedClass)) {
                continue;
            }

            (new $namespacedClass($db))->run();
            $this->info('Database seeding completed successfully.');

            return 0;
        }

        $this->error("Seeder class {$class} not found.");

        return 1;
    }
}
