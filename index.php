<?php
require(__DIR__.'/bootstrap.php');

//set up and munge request/response package
$url = $_SERVER['QUERY_STRING'];
$url = preg_replace('/.*url=/U', '', $url);
$package = new Digraph\Mungers\Package([
    'request.url.original' => $url
]);

$cms->fullMunge($package);

var_dump($package->get());
var_dump($package->log());
echo '<pre>'.$cms->config->yaml().'</pre>';
var_dump($cms->log());
