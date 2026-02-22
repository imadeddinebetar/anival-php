<?php

namespace Core\Debug\Contracts;

/**
 * Contract for the debug bar service.
 */
interface DebugBarInterface
{
    /**
     * Check if the debug bar is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Add a data collector to the debug bar.
     */
    public function addCollector(object $collector): void;

    /**
     * Get a registered collector by class name.
     */
    public function getCollector(string $class): ?object;

    /**
     * Collect data from all registered collectors.
     */
    public function collect(): void;

    /**
     * Get all collected data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array;

    /**
     * Render the debug bar HTML output.
     */
    public function render(): string;
}
