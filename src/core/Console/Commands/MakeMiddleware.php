<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeMiddleware extends Command
{
    protected string $name = 'make:middleware';
    protected string $description = 'Create a new middleware class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Middleware name is required.');
            $this->line('Usage: php bin/anival make:middleware <name>');
            return 1;
        }

        $name = ucfirst($name);

        // Handle namespaced middleware
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $this->getMiddlewareStub();

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $stub
        );

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("Middleware {$className} already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Middleware created successfully: {$path}");

        return 0;
    }

    protected function getPath(string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/app/Middleware';

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . '.php';
    }

    protected function getMiddlewareStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Middleware{{namespace}};

use Core\Http\Message\Request;
use Core\Http\Message\Response;
use Closure;

class {{class}}
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
PHP;
    }
}
