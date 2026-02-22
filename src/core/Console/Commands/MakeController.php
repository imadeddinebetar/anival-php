<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeController extends Command
{
    protected string $name = 'make:controller';
    protected string $description = 'Create a new controller class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Controller name is required.');
            $this->line('Usage: php bin/anival make:controller <name>');
            return 1;
        }

        $name = ucfirst($name);

        // Handle namespaced controllers (e.g., Api/UserController)
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $this->getControllerStub($options['resource'] ?? false);

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $stub
        );

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("Controller {$className} already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Controller created successfully: {$path}");

        return 0;
    }

    protected function getPath(string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/app/Controllers';

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . 'Controller.php';
    }

    protected function getControllerStub(bool $resource = false): string
    {
        if ($resource) {
            return <<<'PHP'
<?php

namespace App\Controllers{{namespace}};

use Core\Http\Message\Response;

class {{class}}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return $this->json(['message' => 'List of {{class}}']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return $this->json(['message' => 'Create form for {{class}}']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(): Response
    {
        return $this->json(['message' => '{{class}} stored'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): Response
    {
        return $this->json(['message' => 'Show {{class}}', 'id' => $id]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Response
    {
        return $this->json(['message' => 'Edit {{class}}', 'id' => $id]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(int $id): Response
    {
        return $this->json(['message' => 'Update {{class}}', 'id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): Response
    {
        return $this->json(['message' => 'Delete {{class}}', 'id' => $id], 200);
    }
}
PHP;
        }

        return <<<'PHP'
<?php

namespace App\Controllers{{namespace}};

use Core\Http\Message\Response;

class {{class}}Controller extends Controller
{
    //
}
PHP;
    }
}
