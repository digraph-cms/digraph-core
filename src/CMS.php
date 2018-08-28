<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph;

use Digraph\Helpers\HelperInterface;
use Digraph\Mungers\Package;
use Flatrr\Config\ConfigInterface;
use Destructr\Drivers\DSODriverInterface;
use Destructr\DSOFactoryInterface;
use Digraph\Mungers\MungerInterface;
use Digraph\Mungers\MungerTree;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class CMS
{
    public $config;
    protected $start;
    protected $log = [];
    protected $values = [];

    public function __construct(ConfigInterface $config)
    {
        $this->start = microtime(true);
        $this->config = $config;
        $this->config->readFile(__DIR__.'/default-config.yaml');
        $this->config['paths.core'] = realpath(__DIR__.'/..');
        $this->log('CMS::__construct finished');
    }

    public function fullMunge(Package &$package)
    {
        foreach ($this->config['fullmunge'] as $name) {
            $this->munge($package, $name);
        }
    }

    public function munge(Package &$package, string $name = 'build')
    {
        $start = microtime(true);
        $this->log('beginning munging with '.$name);
        $package->cms($this);
        $this->munger($name)->munge($package);
        $this->log('finished munging with '.$name.' in '.round((microtime(true)-$start)*1000).'ms');
    }

    public function initializeMungers()
    {
        foreach ($this->config['mungers'] as $treeName => $mungers) {
            $tree = new MungerTree($treeName);
            // set up munger nodes in tree
            ksort($mungers);
            foreach ($mungers as $name => $class) {
                $name = preg_replace('/^[0-9]+\-/', '', $name);
                $munger = new $class($name);
                $tree->add($munger, $name);
            }
            // set up munger hooks
            if ($hooks = $this->config['mungerhooks.'.$treeName]) {
                foreach ($hooks as $hookName => $mungerHooks) {
                    foreach ($mungerHooks as $hookPos => $hookClasses) {
                        foreach ($hookClasses as $hookClassName => $hookClass) {
                            if ($hookPos == 'pre') {
                                $newHook = new $hookClass($hookClassName);
                                $tree->pre($munger, $newHook);
                            }
                            if ($hookPos == 'post') {
                                $newHook = new $hookClass($hookClassName);
                                $tree->post($munger, $newHook);
                            }
                        }
                    }
                }
            }
            // set munger into CMS
            $this->munger($treeName, $tree);
        }
    }

    public function log(string $message = null) : array
    {
        if ($message) {
            $this->log[] = round((microtime(true)-$this->start)*1000).': '.$message;
        }
        return $this->log;
    }

    public function &helper(string $name, HelperInterface &$set=null) : ?HelperInterface
    {
        if ($set) {
            $this->log('Setting helper '.$name.': '.get_class($set));
        } elseif (!$this->valueFunction('helper/'.$name)) {
            if ($class = $this->config['helpers.classes.'.$name]) {
                $this->log('Instantiating helper '.$name);
                @$this->helper($name, new $class($this));
            }
        }
        return $this->valueFunction('helper/'.$name, $set);
    }

    public function &munger(string $name='build', MungerInterface &$set=null) : ?MungerInterface
    {
        if ($set) {
            $set->name($name);
            $this->log('Setting munger '.$name.': '.get_class($set));
            if (method_exists($set, 'cms')) {
                $set->cms($this);
            }
        }
        return $this->valueFunction('munger/'.$name, $set);
    }

    public function &driver(string $name = 'default', DSODriverInterface &$set=null) : ?DSODriverInterface
    {
        if ($set) {
            $this->log('Setting driver '.$name.': '.get_class($set));
            if (method_exists($set, 'cms')) {
                $set->cms($this);
            }
        }
        return $this->valueFunction('driver/'.$name, $set);
    }

    public function &factory(string $name = 'content', DSOFactoryInterface &$set=null) : ?DSOFactoryInterface
    {
        if ($set) {
            $this->log('Setting factory '.$name.': '.get_class($set));
            if (method_exists($set, 'cms')) {
                $set->cms($this);
            }
        }
        return $this->valueFunction('factory/'.$name, $set);
    }

    public function &cache(?string $name = 'default', TagAwareAdapterInterface &$set=null) : ?TagAwareAdapterInterface
    {
        if ($set) {
            $this->log('Setting cache '.$name.': '.get_class($set));
        } elseif (!$this->valueFunction('cache/'.$name)) {
            if ($conf = $this->config['cache.adapters.'.$name]) {
                $this->log('Instantiating cache '.$name);
                $items = @$conf['items']['class'];
                $tags = @$conf['tags']['class'];
                //set up items interface
                $reflector = new \ReflectionClass($items);
                $items = $reflector->newInstanceArgs($conf['items']['args']);
                //set up tags interface
                if ($tags) {
                    $reflector = new \ReflectionClass($tags);
                    $tags = $reflector->newInstanceArgs($conf['tags']['args']);
                }
                $cache = new \Symfony\Component\Cache\Adapter\TagAwareAdapter(
                    $items,
                    $tags
                );
                $this->cache($name, $cache);
            }
        }
        return $this->valueFunction('cache/'.$name, $set);
    }

    protected function &valueFunction(string $name, &$value=null, $default=null)
    {
        if ($value !== null) {
            $this->values[$name] = $value;
        }
        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            return $default;
        }
    }
}
