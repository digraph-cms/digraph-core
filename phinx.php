<?php

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;

require_once __DIR__ . '/vendor/autoload.php';

Digraph::initialize(function () {
    Config::merge([
        'paths.base' => __DIR__ . '/demo',
        'paths.web' => __DIR__ . '/demo',
    ]);
    Config::readFile(__DIR__ . '/env.json');
});

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
