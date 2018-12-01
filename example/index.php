<?php
require __DIR__.'/../vendor/autoload.php';

//set up config
$config = new \Flatrr\Config\Config();
//load site-wide config file
$config->readFile(__DIR__.'/config.yaml');
//load environment config file, overwriting the values in config.yaml
$config->readFile(__DIR__.'/env.yaml', null, true);

/*
Set up site path.
By default this path is used to build the following other paths:
paths.storage = ${paths.site}/storage
paths.cache = ${paths.site}/cache
templates.paths.site = ${paths.site}/templates
templates.twigconfig.cache = ${paths.cache}/twig
modules.paths.site = ${paths.site}/modules
modules.paths.env = ${paths.site}/modules_env
routes.paths.site = ${paths.site}/routes

So the default folder structure in the root of an installation should be:
    /modules     [system read, visitor denied]
    /modules_env [system read, visitor denied]
    /routes      [system read, visitor denied]
    /storage     [system read/write, visitor denied]
    /templates   [system read, visitor denied]
 */
$config['paths.site'] = realpath(__DIR__);

/*
Set cache path to system temp
 */
if (!$config['paths.cache']) {
    $config['paths.cache'] = sys_get_temp_dir().'/digraph-cache';
}

/*
Run bootstrapper. Everything the bootstrapper does can be done manually, but
it isn't adviseable to do it that way.
 */
$cms = \Digraph\Bootstrapper::bootstrap($config);

/*
Set up request/response package, using the Bootstrapper url() method for parsing
the URL out of the query string
 */
$package = new Digraph\Mungers\Package([
    'request.url' => \Digraph\Bootstrapper::url()
]);

/*
Calling fullMunge() will apply the mungers specified in config.fullmunge
By default this means building a response and also rendering it
 */
$cms->fullMunge($package);
