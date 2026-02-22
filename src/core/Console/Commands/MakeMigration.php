<?php

namespace Core\Console\Commands;

/**
 * @internal
 */
class MakeMigration extends Command
{
    protected string $name = 'make:migration';
    protected string $description = 'Create a new migration file';

    public function handle(array $args, array $options): int
    {
        $name = $args[0] ?? null;

        if (!$name) {
            $this->error('Migration name is required.');
            $this->line('Usage: php bin/anival make:migration <name> [--table=]');
            return 1;
        }

        $table = $options['table'] ?? null;

        $timestamp = date('Y_m_d_His');
        $migrationName = "{$timestamp}_{$name}.php";
        $path = dirname(__DIR__, 3) . '/database/migrations/' . $migrationName;

        $stub = $this->getMigrationStub($name, $table);

        $this->createDirectory($path);
        file_put_contents($path, $stub);

        $this->info("Migration created successfully: {$path}");

        return 0;
    }

    protected function getMigrationStub(string $name, ?string $table = null): string
    {
        $tableName = $table ?? $this->generateTableName($name);

        return <<<PHP
<?php

use Core\Database\Internal\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    protected function generateTableName(string $name): string
    {
        // Convert create_users_table -> users
        // Convert add_email_to_users_table -> users
        if (preg_match('/^(create|add).*_(table)$/i', $name)) {
            $name = preg_replace('/^(create|add)_/', '', $name);
            $name = preg_replace('/_table$/', '', $name);
        }

        return strtolower($name);
    }
}
