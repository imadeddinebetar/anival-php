<?php

namespace Database\Seeders;

use Core\Database\Internal\Seeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * 1. Users (no deps)
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);
    }
}