<?php

use DigraphCMS\Digraph;
use DigraphCMS\Initialization\InitializationState;
use DigraphCMS\Initialization\Initializer;
use DigraphCMS\URL\URLs;

require_once __DIR__ . "/../vendor/autoload.php";

Initializer::configureCache(__DIR__ . '/cache', 60);

Initializer::run(
    'initialization',
    function (InitializationState $state) {
        $state->mergeConfig(json_decode(
            file_get_contents(__DIR__ . '/../env.json'),
            true
        ));
        $state->config('paths.base', __DIR__);
        $state->config('paths.web', __DIR__);
    }
);

// special case for running in PHP's built-in server
if (php_sapi_name() === 'cli-server') {
    URLs::$sitePath = '';
    $url = Digraph::actualUrl();
    if ($url->path() == '/favicon.ico' || substr($url->path(), 0, 7) == '/files/') {
        return false;
    }
}

// build and render response
Digraph::renderActualRequest();
