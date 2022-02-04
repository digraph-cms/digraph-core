<?php

use DigraphCMS\Digraph;
use DigraphCMS\Cache\CacheableState;
use DigraphCMS\Cache\CachedInitializer;
use DigraphCMS\Config;
use DigraphCMS\URL\URLs;

require_once __DIR__ . "/../vendor/autoload.php";

// special case for running in PHP's built-in server
if (php_sapi_name() === 'cli-server') {
    URLs::$sitePath = '';
    $url = Digraph::actualUrl();
    if ($url->path() == '/favicon.ico' || substr($url->path(), 0, 7) == '/files/') {
        return false;
    }
}

// CachedInitializer::configureCache(__DIR__ . '/cache', 60);

CachedInitializer::run(
    'initialization',
    function (CacheableState $state) {
        $state->mergeConfig(Config::parseJsonFile(__DIR__.'/../env.json'));
        $state->config('paths.base', __DIR__);
        $state->config('paths.web', __DIR__);
    }
);

// build and render response
Digraph::renderActualRequest();
