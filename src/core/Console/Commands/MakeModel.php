<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeModel extends Command
{
    protected string $name = 'make:model';
    protected string $description = 'Create a new model class';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Model name is required.');
            $this->line('Usage: php bin/anival make:model <name> [-m|--migration] [-f|--factory] [-s|--seeder]');
            return 1;
        }

        $name = ucfirst($name);

        // Generate model
        $modelPath = $this->getModelPath($name);

        if (file_exists($modelPath)) {
            $this->error("Model {$name} already exists!");
            return 1;
        }

        $stub = $this->getModelStub();
        $content = str_replace('{{class}}', $name, $stub);

        $this->createDirectory($modelPath);
        file_put_contents($modelPath, $content);
        $this->info("Model created successfully: {$modelPath}");

        // Generate migration if requested
        if (isset($options['m']) || isset($options['migration'])) {
            $migrationName = 'create_' . strtolower($name) . 's_table';
            $this->createMigration($migrationName, $name);
        }

        // Generate factory if requested
        if (isset($options['f']) || isset($options['factory'])) {
            $this->createFactory($name);
        }

        // Generate seeder if requested
        if (isset($options['s']) || isset($options['seeder'])) {
            $this->createSeeder($name);
        }

        return 0;
    }

    protected function getModelPath(string $name): string
    {
        return dirname(__DIR__, 3) . '/app/Models/' . $name . '.php';
    }

    protected function getModelStub(): string
    {
        return <<<'PHP'
<?php

namespace App\Models;

use Core\Database\Internal\Model;

class {{class}} extends Model
{
    //
}
PHP;
    }

    protected function createMigration(string $name, string $model): void
    {
        $timestamp = date('Y_m_d_His');
        $migrationName = "{$timestamp}_{$name}.php";
        $migrationPath = dirname(__DIR__, 3) . '/database/migrations/' . $migrationName;

        $stub = $this->getMigrationStub($model);

        $this->createDirectory($migrationPath);
        file_put_contents($migrationPath, $stub);
        $this->info("Migration created successfully: {$migrationPath}");
    }

    protected function getMigrationStub(string $model): string
    {
        $table = strtolower($model) . 's';

        return <<<PHP
<?php

use Core\Database\Internal\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;
    }

    protected function createFactory(string $model): void
    {
        $factoryPath = dirname(__DIR__, 3) . '/database/factories/' . $model . 'Factory.php';

        if (file_exists($factoryPath)) {
            $this->warn("Factory {$model}Factory already exists, skipping.");
            return;
        }

        $stub = $this->getFactoryStub($model);

        $this->createDirectory($factoryPath);
        file_put_contents($factoryPath, $stub);
        $this->info("Factory created successfully: {$factoryPath}");
    }

    protected function getFactoryStub(string $model): string
    {
        return <<<PHP
<?php

namespace Database\Factories;

use App\Models\\{$model};
use Illuminate\Database\Eloquent\Factories\Factory;

class {$model}Factory extends Factory
{
    protected \$model = {$model}::class;

    public function definition(): array
    {
        return [
            //
        ];
    }
}
PHP;
    }

    protected function createSeeder(string $model): void
    {
        $seederPath = dirname(__DIR__, 3) . '/database/seeders/' . $model . 'Seeder.php';

        if (file_exists($seederPath)) {
            $this->warn("Seeder {$model}Seeder already exists, skipping.");
            return;
        }

        $stub = $this->getSeederStub($model);

        $this->createDirectory($seederPath);
        file_put_contents($seederPath, $stub);
        $this->info("Seeder created successfully: {$seederPath}");
    }

    protected function getSeederStub(string $model): string
    {
        return <<<PHP
<?php

namespace Database\Seeders;

use App\Models\\{$model};
use Core\Database\Internal\Seeder;

class {$model}Seeder extends Seeder
{
    public function run(): void
    {
        {$model}::factory()->create();
    }
}
PHP;
    }
}
