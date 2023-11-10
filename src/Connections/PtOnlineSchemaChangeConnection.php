<?php

namespace Daursu\ZeroDowntimeMigration\Connections;

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

        $cleanQueries = [];
        foreach($queries as $query) {
            $cleanQueries[] = $this->cleanQuery($query);
        }

        return $this->runProcess(array_merge(
            [
                'pt-online-schema-change',
                $this->isPretending() ? '--dry-run' : '--execute',
            ],
            $this->getAdditionalParameters(),
            [
                '--alter',
                implode(', ', $cleanQueries),
                $this->getAuthString($table),
            ]
        ));
    }
}
