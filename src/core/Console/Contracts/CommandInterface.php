<?php

namespace Core\Console\Contracts;

interface CommandInterface
{
    /**
     * Execute the console command.
     */
    public function handle(array $args, array $options): int;
}
