<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeSeeder extends Command
{
    protected string $name = 'make:seeder';
    protected string $description = 'Create a new seeder class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Seeder name is required.');
            $this->line('Usage: php bin/anival make:seeder <name>');
            return 1;
        }

        $name = ucfirst($name);

        $path = dirname(__DIR__, 3) . '/database/seeders/' . $name . 'Seeder.php';

        if (file_exists($path)) {
            $this->error("Seeder {$name}Seeder already exists!");
            return 1;
        }

        $stub = $this->getSeederStub($name);

        $this->createDirectory($path);
        file_put_contents($path, $stub);

        $this->info("Seeder created successfully: {$path}");

        return 0;
    }

    protected function getSeederStub(string $name): string
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use App\Models\\{$name};
use Core\Database\Internal\Seeder;

class {$name}Seeder extends Seeder
{
    public function run(): void
    {
        {$name}::factory()->create();
    }
}
PHP;
    }
}
