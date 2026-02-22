<?php

namespace Core\Database\Internal;

use Core\Database\Contracts\DatabaseManagerInterface;

/**
 * @internal
 */
class MigrationRepository
{
    public function __construct(private readonly DatabaseManagerInterface $db) {}

    public function ensureTableExists(): void
    {
        $schema = $this->db->schema();

        if ($schema->hasTable('migrations')) {
            return;
        }

        echo "Creating migrations table...\n";

        $schema->create('migrations', function ($table) {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    /**
     * @return array<int, string>
     */
    public function getRanMigrations(): array
    {
        return $this->db->table('migrations')->pluck('migration')->toArray();
    }

    public function getLastBatchNumber(): int
    {
        return (int) ($this->db->table('migrations')->max('batch') ?? 0);
    }

    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * @return iterable<int, object>
     */
    public function getMigrationsForBatch(int $batch): iterable
    {
        return $this->db->table('migrations')
            ->where('batch', $batch)
            ->orderBy('id', 'desc')
            ->get();
    }

    public function log(string $migration, int $batch): void
    {
        $this->db->table('migrations')->insert([
            'migration' => $migration,
            'batch' => $batch,
        ]);
    }

    public function delete(string $migration): void
    {
        $this->db->table('migrations')->where('migration', $migration)->delete();
    }
}
