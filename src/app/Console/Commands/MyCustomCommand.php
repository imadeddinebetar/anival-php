<?php

namespace App\Console\Commands;

use Core\Console\Commands\Command;

class MyCustomCommand extends Command
{
    protected string $name = 'my:command';
    protected string $description = 'A simple custom command example';

    /**
     * @param array<int, string> $args
     * @param array<string, string> $options
     */
    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? 'World';
        $this->info("Hello, {$name}!");

        return 0;
    }
}