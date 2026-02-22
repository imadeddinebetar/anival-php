<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeCommand extends Command
{
    protected string $name = 'make:command';
    protected string $description = 'Create a new custom console command';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Command name is required.');
            $this->line('Usage: php bin/anival make:command <name>');
            return 1;
        }

        $name = ucfirst($name);

        // Handle namespaced commands (e.g. Chat/Server)
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $this->getStub();

        $signature = strtolower($className);
        if (!empty($parts)) {
            $signature = strtolower(implode(':', $parts)) . ':' . $signature;
        }

        $content = str_replace(
            ['{{namespace}}', '{{class}}', '{{signature}}'],
            [$namespace, $className, $signature],
            $stub
        );

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("Command {$className} already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Command created successfully: {$path}");
        $this->line("Next step: Register your command in App\\Console\\Kernel::commands().");

        return 0;
    }

    protected function getPath(string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/app/Console/Commands';

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . '.php';
    }

    protected function getStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Console\Commands{{namespace}};

use Core\Console\Commands\Command;

class {{class}} extends Command
{
    protected string $name = '{{signature}}';
    protected string $description = 'Command description';

    public function handle(array $args, array $options): int
    {
        $this->info('Hello from {{class}}!');

        return 0;
    }
}
PHP;
    }
}
