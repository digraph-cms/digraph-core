<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Pages;
use DigraphCMS\Digraph;

define('START_TIME', microtime(true));

include "../vendor/autoload.php";

Config::readFile(__DIR__ . '/../env.yaml');
Config::merge([
    'paths.base' => __DIR__,
    'paths.web' => __DIR__
]);

// Digraph::renderActualRequest();

// ini_set('memory_limit','10480M');
// set_time_limit(0);
// Dispatcher::$closeResponseBeforeShutdown = false;
// for ($i = 0; $i < 1000000; $i++) {
//     $page = new Page([
//         'foo' => [
//             'bar' => bin2hex(random_bytes(8)),
//             'baz' => bin2hex(random_bytes(12))
//         ]
//     ]);
//     $page->insert();
// }

// $page = Pages::get('d26bda95-9fe0-b1be-551a-cb6e55bdf279');
// var_dump($page['random']);
// $page['random'] = bin2hex(random_bytes(12));
// var_dump($page['random']);
// $page->update();
// echo $page['created'];
// $query = new PageQuery('uuid like ?', ['c888%']);
// $query->execute();
// var_dump($query->fetchAll());
// $query = new PageQuery('uuid like ?', ['%666666%']);
// $query->execute();
// var_dump($query->fetchAll());
// $query = new PageQuery('JSON_VALUE(data,"$.foo.bar") like ?', ['66666%']);
// $query->execute();
// var_dump($query->fetchAll());

$select = Pages::select();
$select->select('class, COUNT(*) as c',true);
$select->where('uuid like ?', ['6666%']);
var_dump($select->fetchPairs('uuid','JSON_VALUE(data,"$.foo.bar")'));

$time = microtime(true) - START_TIME;
$time = round($time * 1000);
echo "<div>{$time}ms " . round(memory_get_peak_usage() / 1024) . "kB</div>";
