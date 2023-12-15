<?php

namespace Daursu\ZeroDowntimeMigration\Transformers\PtOnlineSchemaChange;

use Daursu\ZeroDowntimeMigration\Transformers\DatabaseTransformer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DropOldTables extends DatabaseTransformer
{
    public function transformState(string $tableName, Collection $alterQueries, array $dryRunCommand)
    {
        static::dropOldTables($tableName);
    }

    /**
     * Sometimes tools like `pt-only-schema-change` leave behind cloned table
     * (e.g. `_books_new`) that will cause subsequent operations to blow up.
     * So, we remove them.
     */
    public static function dropOldTables(string $tableName)
    {
        Log::info('Dropping old zero-downtime tables...');

        static::getZeroDowntimeTables($tableName)->each(function ($table) {
            Schema::withoutForeignKeyConstraints(function () use ($table) {
                return Schema::dropIfExists($table);
            });
            Log::info("Dropped zero-downtime table: {$table}");
        });
    }

    /**
     * Get tables created by pt-online-schema-change for zero downtime migrations.
     * These tables are identified by having a name ending with the given table name, prefixed with an underscore.
     *
     * @param string $tableName The base table name to check against.
     * @return Collection<string> An array of tables to be dropped.
     */
    private static function getZeroDowntimeTables(string $tableName): Collection
    {
        $tablePattern1 = '_' . $tableName . '_new';
        $tablePattern2 = '_' . $tableName . '_old';

        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($table) => array_values((array)$table)[0])
            ->filter(fn ($tableName) => Str::endsWith($tableName, $tablePattern1) ||
                Str::endsWith($tableName, $tablePattern2));
    }
}
