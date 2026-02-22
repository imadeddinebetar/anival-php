<?php

namespace Core\Console\Commands;

use Core\Container\Internal\Application;

/**
 * @internal
 */
class AppKeyCommand extends Command
{
    protected string $name = 'app:key';
    protected string $description = 'Generate a new application key';

    public function __construct(private readonly Application $app)
    {
        parent::__construct();
    }

    public function handle(array $args, array $options): int
    {
        $key = bin2hex(random_bytes(16));
        $envPath = $this->app->basePath('.env');

        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_KEY={$key}\nAPP_ENV=local\n");
            $this->info("Created .env file with APP_KEY: {$key}");
            return 0;
        }

        $content = file_get_contents($envPath);

        if ($content === false) {
            $this->error('Failed to read .env file.');
            return 1;
        }

        if (str_contains($content, 'APP_KEY=')) {
            $content = (string) preg_replace('/APP_KEY=.*/', "APP_KEY={$key}", $content);
        } else {
            $content .= "\nAPP_KEY={$key}";
        }

        file_put_contents($envPath, $content);
        $this->info("Application key set successfully: {$key}");

        return 0;
    }
}
