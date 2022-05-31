<?php

declare(strict_types=1);

namespace Daursu\ZeroDowntimeMigration\Connectors;

use  Illuminate\Database\Connectors\MySqlConnector;

class ZeroDowntimeMySqlConnector extends MySqlConnector
{
     /**
     * Get the PDO options based on the configuration.
     *
     * @param  array  $config
     * @return array
     */
    public function getOptions(array $config)
    {
        $options = $config['options'] ?? [];

        if(version_compare(PHP_VERSION, '8.1.0', '>=')){
            //Removing the percona toolkit command options, not necessary for a PDO connection
            $options = array_filter($options, fn($option) => !is_string($option) && substr($option, 0, 2) !== '--');
        }

        return array_diff_key($this->options, $options) + $options;
    }
}