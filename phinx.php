<?php

use DigraphCMS\Config;
use DigraphCMS\DB\DB;

Config::merge([
    'paths.base' => __DIR__.'/demo',
    'paths.web' => __DIR__.'/demo'
]);
Config::readFile(__DIR__ . '/env.json');

return
    [
        'paths' => [
            'migrations' => DB::migrationPaths(),
            'seeds' => []
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'current',
            'current' => Config::get('db')
        ],
        'version_order' => 'creation'
    ];
