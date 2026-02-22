<?php

namespace Core\View\Contracts;

interface ViewFactoryInterface
{
    public function render(string $view, array $data = []): string;
    public function exists(string $view): bool;

    /**
     * Share data with all views.
     *
     * @param array<string, mixed>|string $key
     * @param mixed $value
     */
    public function share(array|string $key, mixed $value = null): void;

    /**
     * Register a view composer (callback fired when a view is rendered).
     *
     * @param string|array<string> $views
     * @param callable|string $callback
     */
    public function composer(string|array $views, callable|string $callback): void;
}
