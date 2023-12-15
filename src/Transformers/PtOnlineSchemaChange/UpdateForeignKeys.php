<?php

namespace Daursu\ZeroDowntimeMigration\Transformers\PtOnlineSchemaChange;

use Daursu\ZeroDowntimeMigration\Transformers\DatabaseTransformer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * @see https://docs.percona.com/percona-toolkit/pt-online-schema-change.html#options
 *
 *   DROP FOREIGN KEY constraint_name requires specifying _constraint_name rather than
 *   the real constraint_name. Due to a limitation in MySQL, pt-online-schema-change adds
 *   a leading underscore to foreign key constraint names when creating the new table.
 *
 * To work around this, this class leverages a dry run to identify the new keys and then
 * transforms the queries aobut to be executed to use the new keys.
 */
class UpdateForeignKeys extends DatabaseTransformer
{
    public function transformQueries(string $tableName, Collection $alterQueries, array $dryRunCommand): Collection
    {
        Log::info('Updating foreign keys...');

        $dryRunOutput = $this->dryRun($dryRunCommand);
        return $this->getTransformedQueries($alterQueries, $dryRunOutput);
    }

    private function dryRun(array $command): string
    {
        $process = new Process($command);
        $process->run();
        $process->stop();

        return $process->getOutput();
    }

    /**
     * @param Collection<string> $queries
     * @return Collection<string>
     */
    private function getTransformedQueries(Collection $queries, string $dryRunOutput): Collection
    {
        $baseNameToNewName = $this->getNewForeignKeyNameMap($dryRunOutput);

        return $queries->map(function ($query) use ($baseNameToNewName) {
            if ($key = str($query)->match("/drop foreign key `(.*?)`/i")->toString()) {
                $baseName = ltrim($key, '_');
                $newName = $baseNameToNewName->get($baseName);

                if ($newName && ($newName !== $key)) {
                    Log::info("Re-mapping foreign key name from $key to $newName.");
                    return str($query)->replace("`$key`", "`$newName`")->value();
                }
            }

            return $query;
        });
    }

    /**
     * @return Collection<string>
     */
    private function getNewForeignKeyNameMap(string $dryRunOutput): Collection
    {
        return str($dryRunOutput)
            ->matchAll("/constraint `(.*?)`/i")
            ->keyBy(fn ($name) => ltrim($name, '_'));
    }
}
