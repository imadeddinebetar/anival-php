<?php

namespace Core\Database\Internal;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Builder;

/**
 * @internal
 */
abstract class Migration
{
    /**
     * Get the schema builder instance.
     */
    protected function schema(): Builder
    {
        return Capsule::schema();
    }

    /**
     * Run the migrations.
     */
    abstract public function up(): void;

    /**
     * Reverse the migrations.
     */
    abstract public function down(): void;
}
