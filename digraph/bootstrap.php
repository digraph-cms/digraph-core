<?php
require __DIR__.'/lib/autoload.php';

//set up config
$config = new \Digraph\Config\Config();
$config->readFile(__DIR__.'/config.yaml');
$config->readFile(__DIR__.'/env.yaml', null, true);

//set up paths if necessary
if (!$config['paths.system']) {
    $config['paths.system'] = __DIR__;
}
if (!$config['paths.site']) {
    $config['paths.site'] = realpath(__DIR__.'/..');
}

//run bootstrapper to set up core features/helpers
$cms = \Digraph\CMS\Bootstrapper::bootstrap($config);
$cms->factory()->createTable();
