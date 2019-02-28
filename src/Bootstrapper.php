<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph;

use Flatrr\Config\ConfigInterface;
use Destructr\DriverFactory;

class Bootstrapper
{
    protected function __construct()
    {
        //static class, shouldn't be constructed
    }

    /**
     * Used to extract the request URL from the querystring, this is designed
     * to work with the example site's Apache/.htaccess url rewriting
     */
    public static function url()
    {
        $url = $_SERVER['QUERY_STRING'];
        $url = preg_replace('/^.*url=/U', '', $url);
        $pos = strpos($url, '&');
        if ($pos !== false) {
            $url = substr_replace($url, '?', $pos, 1);
        }
        $url = urldecode($url);
        return $url;
    }

    public static function bootstrap(ConfigInterface &$config)
    {
        //set up new CMS
        $cms = new CMS($config);
        $cms->log('Bootstrapper::bootstrap starting');
        //set up drivers
        foreach ($config['bootstrap.drivers'] as $k => $c) {
            $class = $c['class'];
            $cred = $config['bootstrap.credentials.'.$c['credentials']];
            if ($class == 'default') {
                $driver = DriverFactory::factory(
                    $cred['dsn'],
                    @$cred['username'],
                    @$cred['password'],
                    @$cred['options']
                );
            } else {
                $driver = new $class(
                    $cred['dsn'],
                    @$cred['username'],
                    @$cred['password'],
                    @$cred['options']
                );
            }
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
        //tell CMS it's time to initialize
        $cms->initialize();
        //set timezone
        date_default_timezone_set($config['timezone']);
        //return
        $cms->log('Bootstrapper::bootstrap finished');
        return $cms;
    }
}
