<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeService extends Command
{
    protected string $name = 'make:service';
    protected string $description = 'Create a new service class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Service name is required.');
            $this->line('Usage: php bin/anival make:service <name>');
            return 1;
        }

        $name = ucfirst($name);

        // Handle namespaced services
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $this->getServiceStub();

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $stub
        );

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("Service {$className}Service already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Service created successfully: {$path}");

        return 0;
    }

    protected function getPath(string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/app/Services';

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . 'Service.php';
    }

    protected function getServiceStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Services{{namespace}};

class {{class}}Service
{
    /**
     * Create a new service instance.
     */
    public function __construct(
        //
    )
    {
        //
    }

    //
}
PHP;
    }
}
