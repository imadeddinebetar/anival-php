<?php

namespace Core\Debug\Internal\Collectors;

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * QueryCollector - Collects database query information for the debug bar
 */
class QueryCollector
{
    protected array $queries = [];
    protected bool $enabled = false;

    public function __construct()
    {
        $this->enabled = env('APP_ENV') === 'local';

        if ($this->enabled) {
            $this->enableQueryLogging();
        }
    }

    protected function enableQueryLogging(): void
    {
        // Enable query logging in the database
        Capsule::connection()->enableQueryLog();
    }

    public function collect(): array
    {
        if (!$this->enabled) {
            return ['queries' => []];
        }

        $queries = Capsule::connection()->getQueryLog();

        // Process queries to add timing information
        $processedQueries = [];
        foreach ($queries as $query) {
            $processedQueries[] = [
                'sql' => $query['query'],
                'bindings' => $query['bindings'] ?? [],
                'time' => isset($query['time']) ? round($query['time'], 2) : 0,
            ];
        }

        return [
            'queries' => $processedQueries,
            'count' => count($processedQueries),
            'total_time' => array_sum(array_column($processedQueries, 'time')),
        ];
    }

    public function reset(): void
    {
        if ($this->enabled) {
            Capsule::connection()->resetQueryLog();
        }
    }
}
