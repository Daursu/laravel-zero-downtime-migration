<?php

namespace Daursu\ZeroDowntimeMigration;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;

/**
 * A variant of `Blueprint` that allows for connection types to define a `statements`
 * method to process an array of SQL query strings at once. For example, pt-online-schema-change
 * lets you pass multiple alter operations to be run on the cloned table.
 */
class BatchableBlueprint extends Blueprint
{
    public function build(Connection $connection, Grammar $grammar)
    {
        if (method_exists($connection, 'statements')) {
            $statements = $this->toSql($connection, $grammar);
            return !empty($statements) ? $connection->statements($statements) : null;
        }

        return parent::build($connection, $grammar);
    }
}
