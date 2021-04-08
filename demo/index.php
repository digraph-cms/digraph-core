<?php
require __DIR__ . '/../vendor/autoload.php';

# set up new config file to get started
$config = new \Flatrr\Config\Config();

# set paths
$config['paths.site'] = realpath(__DIR__);

# set URL
$config['url'] = [
    'protocol' => '//',
    'domain' => $_SERVER['HTTP_HOST'],
    'path' => preg_replace('/index\.php$/', '', $_SERVER['SCRIPT_NAME']),
    'forcehttps' => false,
];

# load config
$config->readFile(__DIR__ . '/digraph.yaml', null, true);
$config->readFile(__DIR__ . '/env.yaml', null, true);

# set up CMS using Bootstrapper
# everything the bootstrapper does can be done manually, but
# in most cases it's better to use it
$cms = \Digraph\Bootstrapper::bootstrap($config);

# load config again to overwrite module settings
$config->readFile(__DIR__ . '/digraph.yaml', null, true);
$config->readFile(__DIR__ . '/env.yaml', null, true);

# set up new request/response package
# it's advisable to use the Bootstrapper url() method for
# getting your query string
$package = new Digraph\Mungers\Package([
    'request.url' => \Digraph\Bootstrapper::url(),
    'request.post' => $_POST,
]);

# calling CMS::fullMunge() will apply the mungers specified
# in the "fullmunge" config
# by default this means building a response and also rendering it
$cms->fullMunge($package);
