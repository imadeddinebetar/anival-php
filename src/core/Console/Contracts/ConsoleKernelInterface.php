<?php

namespace Core\Console\Contracts;

interface ConsoleKernelInterface
{
    /**
     * Handle an incoming CLI command.
     *
     * @param array<int, string> $argv
     */
    public function handle(array $argv): int;

    /**
     * Register a command class for a command name.
     *
     * @param class-string<\Core\Console\Contracts\CommandInterface> $commandClass
     */
    public function registerCommand(string $name, string $commandClass): void;

    /**
     * Get all registered command mappings.
     *
     * @return array<string, class-string<\Core\Console\Contracts\CommandInterface>>
     */
    public function all(): array;
}
