<?php

/**
 * Конфигурация для phinx
 * @see http://docs.phinx.org/en/latest/configuration.html
 */

require_once __DIR__.'/../bff.php';

$databaseName = config::sys('db.name');
$config = [
    'name' => $databaseName,
    'connection' => bff::database()->getPDO(),
];

return [
    'paths' => bff::i()->migrationsPaths(),
    'templates' => [
        'file' => PATH_CORE.'db/migrations/migration.template.php.dist',
    ],
    'environments' => [
        'default_migration_table' => TABLE_MIGRATIONS,
        'default_database'        => $databaseName,
        'production'              => $config,
        'development'             => $config,
    ]
];