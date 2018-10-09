<?php

namespace Daursu\ZeroDowntimeMigration;

use Illuminate\Support\Facades\Schema;

class ZeroDowntimeSchema extends Schema
{
    /**
     * Determines if zero-downtime migrations are enabled.
     *
     * @var bool
     */
    public static $enabled = true;

    /**
     * The name of the zero-downtime database connection.
     * See config/database.php for more details.
     *
     * @var string
     */
    public static $connection = 'zero-downtime';

    /**
     * Disables zero-downtime migrations.
     */
    public static function disable()
    {
        static::$enabled = false;
    }

    /**
     * Enables zero-downtime migrations.
     */
    public static function enable()
    {
        static::$enabled = true;
    }

    /**
     * @param string   $table
     * @param \Closure $callback
     * @return \Illuminate\Database\Schema\Builder
     */
    public static function table(string $table, \Closure $callback)
    {
        if (! static::$enabled) {
            return parent::table($table, $callback);
        }

        return parent::connection(static::$connection)->table($table, $callback);
    }
}
