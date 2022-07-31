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
        $table = $this->extractTableFromQuery($query);

        return $this->runProcess(array_merge(
            [
                'pt-online-schema-change',
                $this->isPretending() ? '--dry-run' : '--execute',
            ],
            $this->getAdditionalParameters(),
            [
                '--alter',
                $this->cleanQuery($query),
                $this->getAuthString($table),
            ]
        ));
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
}
