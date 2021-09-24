<?php

use DigraphCMS\Config;
use DigraphCMS\DB\DB;

require_once __DIR__ . '/vendor/autoload.php';

Config::merge([
    'paths.base' => __DIR__ . '/demo',
    'paths.web' => __DIR__ . '/demo',
]);
Config::readFile(__DIR__ . '/env.json');

return
    [
    'paths' => [
        'migrations' => DB::migrationPaths(),
        'seeds' => [],
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'current',
        'current' => Config::get('db'),
    ],
    'version_order' => 'creation',
];
