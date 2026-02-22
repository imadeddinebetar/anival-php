<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class Down extends Command
{
    protected string $name = 'down';
    protected string $description = 'Put the application into maintenance mode';

    public function handle(array $args, array $options): int
    {
        $message = $options['message'] ?? 'We\'ll be back soon!';
        $retry = $options['retry'] ?? null;

        $data = [
            'message' => $message,
            'retry' => $retry,
            'timestamp' => date('c'),
        ];

        $downFile = dirname(__DIR__, 3) . '/storage/framework/down';

        // Ensure directory exists
        $dir = dirname($downFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($downFile, json_encode($data, JSON_PRETTY_PRINT));

        $this->info('Application is now in maintenance mode.');

        if ($retry) {
            $this->line("Retry after: {$retry} seconds");
        }

        return 0;
    }
}
