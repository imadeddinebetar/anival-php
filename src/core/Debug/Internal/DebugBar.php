<?php

namespace Core\Debug\Internal;

use Core\Container\Internal\Application;
use Core\Debug\Contracts\DebugBarInterface;

/**
 * DebugBar - Development debugging toolbar
 *
 * Collects and displays debug information in development mode.
 *
 * @internal
 */
class DebugBar implements DebugBarInterface
{
    protected Application $app;
    protected bool $enabled = false;
    protected array $collectors = [];
    protected array $data = [];
    protected float $startTime;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->startTime = microtime(true);
        $this->enabled = env('APP_ENV') === 'local' && config('app.debug_bar', false);
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function addCollector(object $collector): void
    {
        if (method_exists($collector, 'collect')) {
            $this->collectors[get_class($collector)] = $collector;
        }
    }

    public function getCollector(string $class): ?object
    {
        return $this->collectors[$class] ?? null;
    }

    public function collect(): void
    {
        foreach ($this->collectors as $name => $collector) {
            $this->data[$name] = $collector->collect();
        }

        // Add timing information
        $this->data['time'] = [
            'start' => $this->startTime,
            'end' => microtime(true),
            'duration' => microtime(true) - $this->startTime,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function render(): string
    {
        if (!$this->isEnabled()) {
            return '';
        }

        $this->collect();

        return $this->renderHtml();
    }

    protected function renderHtml(): string
    {
        $data = $this->getData();

        $queries = $data['Core\\Debug\\Collectors\\QueryCollector']['queries'] ?? [];
        $queryCount = count($queries);
        $queryTime = array_sum(array_column($queries, 'time')) ?? 0;

        $routes = $data['Core\\Debug\\Collectors\\RouteCollector']['route'] ?? null;

        $html = <<<HTML
<div id="anival-debug-bar" style="position: fixed; bottom: 0; left: 0; right: 0; background: #2d2d2d; color: #fff; padding: 8px 16px; font-family: monospace; font-size: 12px; z-index: 99999; display: flex; justify-content: space-between; align-items: center;">
    <div style="display: flex; gap: 20px;">
        <span><strong>Anival Debug</strong></span>
        <span>Queries: {$queryCount} ({$queryTime}ms)</span>
HTML;

        if ($routes) {
            $routeName = isset($routes['name']) ? $routes['name'] : 'N/A';
            $routePath = $routes['path'];
            $html .= "<span>Route: {$routeName}</span>";
            $html .= "<span>URI: {$routePath}</span>";
        }

        $duration = $data['time']['duration'] ?? 0;
        $html .= <<<HTML
        <span>Time: {$duration}ms</span>
    </div>
    <div>
        <button onclick="document.getElementById('anival-debug-details').style.display = document.getElementById('anival-debug-details').style.display === 'none' ? 'block' : 'none'" style="background: #444; color: #fff; border: none; padding: 4px 8px; cursor: pointer;">Details</button>
    </div>
</div>
<div id="anival-debug-details" style="display: none; position: fixed; bottom: 40px; left: 0; right: 0; max-height: 300px; overflow-y: auto; background: #1a1a1a; color: #ddd; padding: 16px; font-family: monospace; font-size: 11px; z-index: 99998;">
HTML;

        // Queries section
        if (!empty($queries)) {
            $html .= '<h4 style="margin: 8px 0; color: #98c379;">Database Queries</h4>';
            $html .= '<table style="width: 100%; border-collapse: collapse;">';
            $html .= '<tr style="background: #333;"><th style="padding: 4px; text-align: left;">SQL</th><th style="padding: 4px; text-align: right;">Time</th></tr>';
            foreach ($queries as $query) {
                $sql = htmlspecialchars($query['sql'] ?? '');
                $time = $query['time'] ?? 0;
                $html .= "<tr><td style='padding: 2px 4px; border-bottom: 1px solid #333;'>{$sql}</td><td style='padding: 2px 4px; text-align: right; border-bottom: 1px solid #333;'>{$time}ms</td></tr>";
            }
            $html .= '</table>';
        }

        $html .= '</div>';

        return $html;
    }
}
