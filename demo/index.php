<?php

use DigraphCMS\Config;
use DigraphCMS\Digraph;
use DigraphCMS\URL\URLs;

require_once __DIR__ . "/../vendor/autoload.php";

// set up config
Digraph::initialize(
    function () {
        Config::readFile(__DIR__ . '/../env.json');
        Config::set('paths.base', __DIR__);
        Config::set('paths.web', __DIR__);
    },
    __DIR__.'/cache',
    60
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
