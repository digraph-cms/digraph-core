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

// $page = new Page([],[
//     'slug'=>'fdd2b9ba-59ab-7769-62de-68a11b4dadb6'
// ]);
// $page->insert();
// var_dump($page);

// ini_set('memory_limit','10480M');
// set_time_limit(0);
// DB::pdo()->beginTransaction();
// for ($i = 0; $i < 10000; $i++) {
//     $page = new Page([
//         'foo' => [
//             'bar' => bin2hex(random_bytes(8)),
//             'baz' => bin2hex(random_bytes(12))
//         ]
//     ]);
//     $page->insert();
// }
// DB::pdo()->commit();

// Graph::removeLink('foo','bar');
// $page = Pages::get('ace00b01');
// var_dump($page);

$time = microtime(true) - START_TIME;
$time = round($time * 1000);
echo "<div>{$time}ms " . round(memory_get_peak_usage() / 1024) . "kB</div>";
