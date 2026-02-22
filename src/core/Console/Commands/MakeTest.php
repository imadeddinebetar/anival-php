<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeTest extends Command
{
    protected string $name = 'make:test';
    protected string $description = 'Create a new test class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Test name is required.');
            $this->line('Usage: php anival make:test <name> [--unit]');
            return 1;
        }

        $name = ucfirst($name);

        // Ensure the name ends with "Test"
        if (!str_ends_with($name, 'Test')) {
            $name .= 'Test';
        }

        // Determine type: --unit flag uses Unit/ dir with base TestCase, otherwise Feature/
        $isUnit = isset($options['unit']);
        $typeDir = $isUnit ? 'Unit' : 'Feature';

        // Handle namespaced tests (e.g. Auth/GateTest)
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $subNamespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $isUnit ? $this->getUnitStub() : $this->getFeatureStub();

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$subNamespace, $className],
            $stub
        );

        $path = $this->getTestPath($typeDir, $name);

        if (file_exists($path)) {
            $this->error("Test {$className} already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Test created successfully: {$path}");

        return 0;
    }

    protected function getTestPath(string $typeDir, string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/tests/' . $typeDir;

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . '.php';
    }

    protected function getUnitStub(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Unit{{namespace}};

use PHPUnit\Framework\TestCase;

class {{class}} extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
PHP;
    }

    protected function getFeatureStub(): string
    {
        return <<<'PHP'
<?php

namespace Tests\Feature{{namespace}};

use Tests\TestCase;

class {{class}} extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_example(): void
    {
        $this->assertTrue(true);
    }
}
PHP;
    }
}
