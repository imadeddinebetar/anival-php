<?php

namespace Core\View\Internal;

use Illuminate\Contracts\View\Factory;
use Core\View\Contracts\ViewFactoryInterface;

/**
 * @internal
 */
class View implements ViewFactoryInterface
{
    protected Factory $factory;

    /** @var array<string, mixed> */
    protected array $shared = [];

    /** @var array<string, array<callable>> */
    protected array $composers = [];

    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    public function render(string $view, array $data = []): string
    {
        // Merge shared data
        $data = array_merge($this->shared, $data);

        // Execute composers for this view
        foreach ($this->composers as $pattern => $callbacks) {
            if ($pattern === $view || fnmatch($pattern, $view)) {
                foreach ($callbacks as $callback) {
                    $callback($data);
                }
            }
        }

        return $this->factory->make($view, $data)->render();
    }

    public function exists(string $view): bool
    {
        return $this->factory->exists($view);
    }

    public function share(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }

        // Also share with the Blade factory
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->factory->share($k, $v);
            }
        } else {
            $this->factory->share($key, $value);
        }
    }

    public function composer(string|array $views, callable|string $callback): void
    {
        $views = (array) $views;

        foreach ($views as $view) {
            if (!isset($this->composers[$view])) {
                $this->composers[$view] = [];
            }
            $this->composers[$view][] = $callback;
        }
    }
}
