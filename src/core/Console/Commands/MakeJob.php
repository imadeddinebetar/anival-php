<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeJob extends Command
{
    protected string $name = 'make:job';
    protected string $description = 'Create a new job class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Job name is required.');
            $this->line('Usage: php bin/anival make:job <name>');
            return 1;
        }

        $name = ucfirst($name);

        // Handle namespaced jobs
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $this->getJobStub(isset($options['sync']) || isset($options['s']));

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $stub
        );

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("Job {$className} already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Job created successfully: {$path}");

        return 0;
    }

    protected function getPath(string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/app/Jobs';

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . '.php';
    }

    protected function getJobStub(bool $sync = false): string
    {
        if ($sync) {
            return <<<'PHP'
<?php

namespace App\Jobs{{namespace}};

class {{class}}
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        //
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}
PHP;
        }

        return <<<'PHP'
<?php

namespace App\Jobs{{namespace}};

use Core\Queue\Batch\Batchable;

class {{class}}
{
    use Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        //
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(array $data): void
    {
        //
    }
}
PHP;
    }
}
