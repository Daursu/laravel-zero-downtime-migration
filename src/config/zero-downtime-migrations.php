<?php

return [
    'transformers' => [
        \Daursu\ZeroDowntimeMigration\Transformers\PtOnlineSchemaChange\DropOldTables::class,
        \Daursu\ZeroDowntimeMigration\Transformers\PtOnlineSchemaChange\DropOldTriggers::class,
        \Daursu\ZeroDowntimeMigration\Transformers\PtOnlineSchemaChange\UpdateForeignKeys::class
    ],
];
