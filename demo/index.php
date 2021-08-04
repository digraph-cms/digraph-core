<?php

use DigraphCMS\Config;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;

include "../vendor/autoload.php";

Config::readFile(__DIR__ . '/../env.yaml');
Config::merge([
    'paths.base' => __DIR__,
    'paths.web' => __DIR__
]);

Dispatcher::$closeResponseBeforeShutdown = false;
Digraph::renderActualRequest();
