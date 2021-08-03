<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\DB\DB;
use DigraphCMS\DB\SqliteShim;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;

define('START_TIME', microtime(true));

include "../vendor/autoload.php";

Config::readFile(__DIR__ . '/../env.yaml');
Config::merge([
    'paths.base' => __DIR__,
    'paths.web' => __DIR__
]);

Dispatcher::$closeResponseBeforeShutdown = false;
Digraph::renderActualRequest();

$time = microtime(true) - START_TIME;
$time = round($time * 1000);
echo "<div>{$time}ms " . round(memory_get_peak_usage() / 1024) . "kB</div>";
