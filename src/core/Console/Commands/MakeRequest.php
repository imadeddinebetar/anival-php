<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeRequest extends Command
{
    protected string $description = 'Create a new form request class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Request name is required.');
            $this->line('Usage: php bin/anival make:request <name>');
            return 1;
        }

        $name = ucfirst($name);

        // Handle namespaced requests
        $parts = explode('/', $name);
        $className = array_pop($parts);
        $namespace = !empty($parts) ? '\\' . implode('\\', $parts) : '';

        $stub = $this->getRequestStub();

        $content = str_replace(
            ['{{namespace}}', '{{class}}'],
            [$namespace, $className],
            $stub
        );

        $path = $this->getPath($name);

        if (file_exists($path)) {
            $this->error("Request {$className}Request already exists!");
            return 1;
        }

        $this->createDirectory($path);
        file_put_contents($path, $content);

        $this->info("Request created successfully: {$path}");

        return 0;
    }

    protected function getPath(string $name): string
    {
        $parts = explode('/', $name);
        $path = dirname(__DIR__, 3) . '/app/Requests';

        if (count($parts) > 1) {
            $path .= '/' . implode('/', array_slice($parts, 0, -1));
        }

        return $path . '/' . ucfirst(end($parts)) . 'Request.php';
    }

    protected function getRequestStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Requests{{namespace}};

use Core\Container\Internal\Application;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class {{class}}Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'errors' => $validator->errors()
        ], 422));
    }
}
PHP;
    }
}
