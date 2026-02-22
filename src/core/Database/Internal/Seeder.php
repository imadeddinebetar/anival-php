<?php

namespace Core\Database\Internal;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * @internal
 */
abstract class Seeder
{
    /**
     * The database manager instance.
     */
    protected DatabaseManager $db;

    /**
     * Create a new seeder instance.
     */
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    /**
     * Run the database seeds.
     */
    abstract public function run(): void;

    /**
     * Call another seeder class.
     *
     * @param string|array $class
     * @return void
     */
    public function call(string|array $class): void
    {
        $classes = (array) $class;

        foreach ($classes as $class) {
            if (class_exists($class)) {
                (new $class($this->db))->run();
            } else {
                // Try to find it in App\Database\Seeds namespace
                $namespacedClass = "App\\Database\\Seeders\\" . $class;
                if (class_exists($namespacedClass)) {
                    (new $namespacedClass($this->db))->run();
                }
            }
        }
    }
}
