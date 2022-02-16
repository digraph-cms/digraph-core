<?php

use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Config;
use DigraphCMS\DB\DB;

require_once __DIR__ . '/vendor/autoload.php';

// run initial configuration
CachedInitializer::run(
    'initialization',
    function (CacheableState $state) {
        $state->mergeConfig(Config::parseJsonFile(__DIR__ . '/env.json'), true);
        $state->config('paths.base', __DIR__.'/demo');
        $state->config('paths.web', __DIR__.'/demo');
    }
);

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
