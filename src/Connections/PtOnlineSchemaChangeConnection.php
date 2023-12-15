<?php

namespace Daursu\ZeroDowntimeMigration\Connections;

use Daursu\ZeroDowntimeMigration\Transformers\DatabaseTransformer;
use Illuminate\Support\Collection;

class PtOnlineSchemaChangeConnection extends BaseConnection
{
    /**
     * Executes the SQL statement through pt-online-schema-change.
     *
     * @param  string $query
     * @param  array  $bindings
     * @return bool|int
     */
    public function statement($query, $bindings = [])
    {
        return $this->runQueries([$query]);
    }

    /**
     * A custom connection method called by our custom schema builder to help batch
     * operations on the cloned table.
     *
     * @see \Daursu\ZeroDowntimeMigration\BatchableBlueprint
     *
     * @param string $query
     * @param array $bindings
     * @return bool|int
     */
    public function statements($queries, $bindings = [])
    {
        return $this->runQueries($queries);
    }

    /**
     * @param  string $table
     * @return string
     */
    protected function getAuthString(string $table): string
    {
        return sprintf(
            'h=%s,P=%s,D=%s,u=%s,p=%s,t=%s',
            $this->getConfig('host'),
            $this->getConfig('port'),
            $this->getConfig('database'),
            $this->getConfig('username'),
            $this->getConfig('password'),
            $table
        );
    }

    /**
     * Hide the username/pw from console output.
     *
     * @param array $command
     * @return string
     */
    protected function maskSensitiveInformation(array $command): string
    {
        return collect($command)->map(function ($config) {
            $config = preg_replace('/('.preg_quote($this->getConfig('password'), '/').'),/', '*****,', $config);

            return preg_replace('/('.preg_quote($this->getConfig('username'), '/').'),/', '*****,', $config);
        })->implode(' ');
    }

    /**
     * @param string[] $queries
     * @return bool|int
     */
    protected function runQueries($queries)
    {
        $table = $this->extractTableFromQuery($queries[0]);
        $cleanQueries = $this->applyTransformers($table, $queries);

        $runCommand = $this->makeCommand($table, $cleanQueries, $this->isPretending());
        return $this->runProcess($runCommand);
    }

    /**
     * @return array<string>
     */
    protected function makeCommand(string $table, Collection $queries, bool $dryRun = false): array
    {
        // array_filter to strip empty lines from `getAdditionalParameters`
        return array_filter(array_merge(
            ['pt-online-schema-change', $dryRun ? '--dry-run' : '--execute'],
            $this->getAdditionalParameters(),
            ['--alter', $queries->join(', '), $this->getAuthString($table)]
        ));
    }

    /**
     * @return Collection<string>
     */
    protected function applyTransformers(string $tableName, array $queries): Collection
    {
        $cleanQueries = collect($queries)->map(fn (string $query) => $this->cleanQuery($query));
        $dryRunCommand = $this->makeCommand($tableName, $cleanQueries, true);

        foreach ($this->getDatabaseTransformers() as $transformer) {
            $transformer->transformState($tableName, $cleanQueries, $dryRunCommand);
            $cleanQueries = $transformer->transformQueries($tableName, $cleanQueries, $dryRunCommand);
        }

        return $cleanQueries;
    }

    /**
     * @return Collection<DatabaseTransformer>
     */
    protected function getDatabaseTransformers(): Collection
    {
        $trasnformerClasses = config('zero-downtime-migrations.transformers');

        return collect($trasnformerClasses)->map(fn (string $className) => new $className());
    }
}
