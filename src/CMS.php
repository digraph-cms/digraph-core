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
    protected $readCache = [];

    public function __construct(ConfigInterface $config)
    {
        $this->start = microtime(true);
        $this->config = $config;
        $this->config->readFile(__DIR__.'/../default-config.yaml');
        $this->config->readFile(__DIR__.'/../default-strings.yaml', 'strings');
        $this->config['paths.core'] = realpath(__DIR__.'/..');
        $this->log('CMS::__construct finished');
    }

    /**
     * Uses the names in config[helpers.initialized] to initialize any helpers
     * that should be prepared right off the bat. This is useful for things like
     * having the modules helper initialize to load all modules, or having the
     * lang helper initialize immediately to load language config.
     *
     * Also initializes mungers
     */
    public function initialize()
    {
        //initialize modules
        foreach ($this->config['helpers.initialized'] as $name => $i) {
            if ($i) {
                $this->helper($name)->initialize();
            }
        }
        //initialize mungers
        $this->initializeMungers();
    }

    public function read(string $q = null, bool $slugs = true)
    {
        if (!$q) {
            return null;
        }
        $q = trim($q, "/ \t\n\r\0\x0B");
        $id = md5(serialize([$q,$slugs]));
        if (!isset($this->readCache[$id])) {
            $search = $this->factory()->search();
            if ($slugs) {
                $search->where('${dso.id} = :search OR ${digraph.slug} = :search');
            } else {
                $search->where('${dso.id} = :search');
            }
            $result = @array_shift($search->execute([':search'=>$q]));
            if ($result) {
                $this->readCache[$id] = $result;
            } else {
                $this->readCache[$id] = false;
            }
        }
        return $this->readCache[$id];
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
            $set->name($name);
            $set->createTable();
            $this->config->push('factories', $name);
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

    public function invalidateCache(string $dso_id)
    {
        $this->cache()->invalidateTags([$dso_id]);
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
