<?php

namespace Daursu\ZeroDowntimeMigration;

use Illuminate\Database\Schema\Blueprint;

/**
 * A variant of `Blueprint` that allows for connection types to define a `statements`
 * method to process an array of SQL query strings at once. For example, pt-online-schema-change
 * lets you pass multiple alter operations to be run on the cloned table.
 */
class BatchableBlueprint extends Blueprint
{
    public function build()
    {
        if (method_exists($this->connection, 'statements')) {
            $statements = $this->toSql();
            return !empty($statements) ? $this->connection->statements($statements) : null;
        }

        return parent::build();
    }
}
