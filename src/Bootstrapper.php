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

    protected static function buildFactories(&$cms, &$config=null)
    {
        if (!$config) {
            $config = $cms->config;
        }
        //set up bare PDOs
        if ($config['bootstrap.pdos']) {
            foreach ($config['bootstrap.pdos'] as $k => $cred) {
                $pdo = new \PDO(
                    $cred['dsn'],
                    @$cred['username'],
                    @$cred['password'],
                    @$cred['options']
            );
                $cms->pdo($k, $pdo);
            }
        }
        //set up Destructr drivers
        if ($config['bootstrap.drivers']) {
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
        }
        //set up Destructr factories
        if ($config['bootstrap.factories']) {
            foreach ($config['bootstrap.factories'] as $k => $c) {
                $class = $c['class'];
                $factory = new $class(
                    $cms->driver($c['driver']),
                    $c['table']
            );
                $cms->factory($k, $factory);
            }
        }
        //unset bootstrap settings
        unset($config['bootstrap']);
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
        //set up factories from bootstrap
        static::buildFactories($cms, $config);
        //tell CMS it's time to initialize
        $cms->initialize();
        //after initialization build factories again, so modules can specify
        //their own bootstrap settings
        static::buildFactories($cms);
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
            $get = $_GET;
            unset($get['digraph_url']);
            $get['digraph_redirect_count'] = @$get['digraph_redirect_count']+1;
            if ($get) {
                $newUrl .= '?'.http_build_query($get);
            }
            if ($get['digraph_redirect_count'] <= 3) {
                header("Location: $newUrl");
                die('Attempting to force HTTPS by redirecting to ' . $newUrl);
            }
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
