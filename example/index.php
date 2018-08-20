<?php
require __DIR__.'/../vendor/autoload.php';

//set up config
$config = new \Flatrr\Config\Config();
$config->readFile(__DIR__.'/config.yaml');
// $config->readFile(__DIR__.'/env.yaml', null, true);

/*
Set up paths, the ones that are required are:

 */
if (!$config['paths.system']) {
    $config['paths.system'] = __DIR__;
}
if (!$config['paths.site']) {
    $config['paths.site'] = realpath(__DIR__);
}

//run bootstrapper to set up core features/helpers
$cms = \Digraph\Bootstrapper::bootstrap($config);
$cms->factory()->createTable();

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
