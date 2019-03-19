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
        if (!($url = @$_GET['digraph_url'])) {
            $url = '';
        }
        $q = [];
        foreach ($_GET as $key => $value) {
            if ($key != 'digraph_url') {
                $q[] = urlencode($key).'='.urlencode($value);
            }
        }
        if ($q) {
            $url .= '?'.implode('&', $q);
        }
        return $url;
    }

    public static function bootstrap(ConfigInterface &$config)
    {
        //set up new CMS
        $cms = new CMS($config);
        $cms->log('Bootstrapper::bootstrap starting');
        //do https redirects if necessary
        if ($cms->config['url.forcehttps']) {
            static::makeHttps();
        }
        //set up bare PDOs
        foreach ($config['bootstrap.pdos'] as $k => $cred) {
            $pdo = new \PDO(
                $cred['dsn'],
                @$cred['username'],
                @$cred['password'],
                @$cred['options']
            );
            $cms->pdo($k, $pdo);
        }
        //set up Destructr drivers
        foreach ($config['bootstrap.drivers'] as $k => $c) {
            $class = $c['class'];
            $pdo = $cms->pdo($c['pdo']);
            if ($class == 'default') {
                $driver = DriverFactory::factoryFromPDO($pdo);
            } else {
                $driver = new $class();
                $driver->pdo($pdo);
            }
            $cms->driver($k, $driver);
        }
        //set up Destructr factories
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

    public static function makeHttps()
    {
        if (!static::isHttps()) {
            $newUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $newUrl = preg_replace('/index\.(php)$/i', '', $newUrl);
            if (count($_GET) >= 1) {
                $newUrl .= '?'.http_build_query($_GET);
            }
            header("Location: $newUrl");
            die('Attempting to force HTTPS by redirecting to ' . $newUrl);
        }
    }

    public static function isHttps()
    {
        //use $_SERVER['HTTPS'] if it exists
        if (isset($_SERVER['HTTPS'])) {
            return $_SERVER['HTTPS'] !== 'off';
        }
        //if we're on port 443 return true
        if ($_SERVER['SERVER_PORT'] == 443) {
            return true;
        }
        //as a fallback, set a cookie that the browser is asked to only
        //send back over HTTPS. Then if this cookie is set, we know we're on
        //an SSL connection.
        if (!isset($_COOKIE['Digraph_HTTPS_Only_Cookie'])) {
            setcookie('Digraph_HTTPS_Only_Cookie', time(), 0, "", "", true);
            return false;
        }
        return true;
    }
}
