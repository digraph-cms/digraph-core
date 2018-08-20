<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph;

use Flatrr\Config\ConfigInterface;

class Bootstrapper
{
    protected function __construct()
    {
        //static class, shouldn't be constructed
    }

    public static function bootstrap(ConfigInterface &$config)
    {
        $cms = new CMS($config);
        $cms->log('Bootstrapper::bootstrap starting');
        //set up drivers
        foreach ($config['bootstrap.drivers'] as $k => $c) {
            $class = $c['class'];
            $cred = $config['bootstrap.credentials.'.$c['credentials']];
            $driver = new $class(
                $cred['dsn'],
                $cred['username'],
                $cred['password'],
                $cred['options']
            );
            $cms->driver($k, $driver);
        }
        //set up factories
        foreach ($config['bootstrap.factories'] as $k => $c) {
            $class = $c['class'];
            $factory = new $class(
                $cms->driver($c['driver']),
                $c['table']
            );
            $cms->factory($k, $factory);
        }
        //unset bootstrap settings
        unset($config['bootstrap']);
        //initialize modules
        $cms->helper('modules')->initialize();
        //initialize mungers
        $cms->initializeMungers();
        //return
        $cms->log('Bootstrapper::bootstrap finished');
        return $cms;
    }
}
