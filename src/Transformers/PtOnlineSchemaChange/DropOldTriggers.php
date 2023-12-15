<?php

namespace Daursu\ZeroDowntimeMigration\Transformers\PtOnlineSchemaChange;

use Daursu\ZeroDowntimeMigration\Transformers\DatabaseTransformer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DropOldTriggers extends DatabaseTransformer
{

    public function transformState(string $tableName, Collection $alterQueries, array $dryRunCommand)
    {
        static::dropOldTriggers($tableName);
    }

    /**
     * During the cloning phase, tools like `pt-only-schema-change` sometimes leave behind
     * triggers like `pt_osc_*` if they failed, which then blows up with obscure `--preserve-triggers`
     * errors later on. So, we remove them.
     */
    public static function dropOldTriggers($tableName)
    {
        Log::info('Dropping old zero-downtime triggers...');

        static::getZeroDowntimeTriggers($tableName)->each(function ($trigger) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$trigger}");
            Log::info("Dropped zero-downtime trigger: {$trigger}");
        });
    }

    /**
     * Get triggers associated with zero downtime migration process, typically starting with 'pt_osc_'.
     *
     * @param string $tableName The base table name to check triggers against.
     * @return Collection<string> An array of triggers to be dropped.
     */
    private static function getZeroDowntimeTriggers(string $tableName): Collection
    {
        return collect(DB::select("SHOW TRIGGERS WHERE `Trigger` LIKE 'pt_osc_%' AND `Table` = ?", [$tableName]))
            ->pluck('Trigger');
    }
}
