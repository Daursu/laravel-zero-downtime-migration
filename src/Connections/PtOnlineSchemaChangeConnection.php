<?php

namespace Daursu\ZeroDowntimeMigration\Connections;

use Illuminate\Database\MySqlConnection;
use Symfony\Component\Process\Process;

class PtOnlineSchemaChangeConnection extends MySqlConnection
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
        $table = $this->extractTableFromQuery($query);

        return $this->runProcess(array_merge(
            [
                'pt-online-schema-change',
                '--execute',
            ],
            array_get($this->config, 'options', []),
            [
                '--alter',
                $this->cleanQuery($query),
                $this->getAuthString($table),
            ]
        ));
    }

    /**
     * Runs the pt-online-schema-change process.
     *
     * @param array $command
     * @return int
     */
    public function runProcess(array $command): int
    {
        $process = new Process($command);
        $process->mustRun();

        return $process->stop();
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
     * Returns the table name.
     *
     * @param  string $query
     * @return string
     */
    protected function extractTableFromQuery(string $query): string
    {
        preg_match('/table `(.*?)`/', $query, $matches);

        return array_get($matches, '1');
    }

    /**
     * Removes the table name from the query.
     *
     * @param  string $query
     * @return string
     */
    protected function cleanQuery(string $query): string
    {
        $table = $this->extractTableFromQuery($query);
        $pos = strpos($query, $table.'`');

        return trim(substr($query, $pos + strlen($table) + 1));
    }
}
