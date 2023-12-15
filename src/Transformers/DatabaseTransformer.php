<?php

namespace Daursu\ZeroDowntimeMigration\Transformers;

use Illuminate\Support\Collection;

/**
 * Transformers are classes executed just before running zero-downtime migrations
 * to help account for common "gotchas" in different zero-downtime packages.
 */
abstract class DatabaseTransformer
{
    /**
     * Execute queries altering the state of the database here. For example, dropping
     * leftover triggers.
     *
     * @param string $tableName
     * @param Collection<string> $alterQueries
     * @param array $dryRunCommand
     * @return void
     */
    public function transformState(string $tableName, Collection $alterQueries, array $dryRunCommand)
    {
        // No-op
    }

    /**
     * Returns modifies versions of the queries that were originally going to be executed.
     * For example, for updating column names and such to reflect their new names in the
     * cloned table.
     *
     * @param string $tableName
     * @param Collection<string> $alterQueries
     * @param array $dryRunCommand
     * @return Collection<string>
     */
    public function transformQueries(string $tableName, Collection $alterQueries, array $dryRunCommand): Collection
    {
        return $alterQueries;
    }
}
