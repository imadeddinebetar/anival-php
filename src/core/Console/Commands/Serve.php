<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class Serve extends Command
{
    protected string $name = 'serve';
    protected string $description = 'Start the development server';

    public function handle(array $args, array $options): int
    {
        $host = $options['host'] ?? '127.0.0.1';
        $port = $options['port'] ?? 8000;

        // Check if port is available
        $socket = @fsockopen($host, $port, $errno, $errstr, 1);
        if ($socket) {
            fclose($socket);
            $this->error("Port {$port} is already in use.");
            return 1;
        }

        $this->info("Starting development server on http://{$host}:{$port}");
        $this->line("Press Ctrl+C to stop the server");

        $command = sprintf(
            'php -S %s:%d -t %s/public',
            $host,
            $port,
            dirname(__DIR__, 3)
        );

        passthru($command);

        return 0;
    }
}
