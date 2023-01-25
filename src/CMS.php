<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */

namespace Digraph;

use Destructr\Drivers\AbstractDriver;
use Destructr\Factory;
use Digraph\DSO\Noun;
use Digraph\Helpers\HelperInterface;
use Digraph\Logging\LogHelper;
use Digraph\Mungers\MungerInterface;
use Digraph\Mungers\MungerTree;
use Digraph\Mungers\PackageInterface;
use Flatrr\Config\ConfigInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;

class CMS
{
    public $config;
    protected $start;
    protected $log = [];
    protected $values = [];
    protected $readCache = [];
    protected $package;
    protected $helpers = [];
    protected $caches = [];

    public function __construct(ConfigInterface $config)
    {
        $this->start = microtime(true);
        $this->config = $config;
        $this->config->readFile(__DIR__ . '/../default-config.json');
        $this->config->readFile(__DIR__ . '/../default-strings.json', 'strings');
        $this->config['paths.core'] = realpath(__DIR__ . '/..');
        $this->log('CMS::__construct finished');
        //register hooks for invalidating caches by noun IDs
        $this->helper('hooks')->noun_register('update', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('parent:update', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('child:update', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('insert', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('parent:insert', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('child:insert', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('delete', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('parent:delete', [$this, 'invalidateCache'], 'cms/invalidateCache');
        $this->helper('hooks')->noun_register('child:delete', [$this, 'invalidateCache'], 'cms/invalidateCache');
    }

    public function package(PackageInterface $package = null): ?PackageInterface
    {
        if ($package) {
            $this->package = $package;
        }
        return $this->package;
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
        $this->helper('modules')->initialize();
        foreach ($this->config['helpers.initialized'] as $name => $i) {
            if ($i) {
                $this->helper($name)->initialize();
            }
        }
        //initialize mungers
        $this->initializeMungers();
    }

    /**
     * Runs after final factory creation, as the absolute last step of bootstrapping
     *
     * @return void
     */
    public function finalInitialize()
    {
        //set up additional columns in factories
        foreach ($this->config['factory_columns'] as $name => $cols) {
            $factory = $this->factory($name);
            foreach ($cols as $path => $col) {
                $path = str_replace('__', '.', $path);
                $factory->addColumn($path, $col);
            }
        }
        //set up factory environments
        foreach ($this->config['factories'] as $name) {
            $this->factory($name)->prepareEnvironment();
        }
    }

    public function locate(string $q = null, bool $slugs = true, bool $recache = false)
    {
        if (!$q) {
            return [];
        }
        $q = trim($q, "/ \t\n\r\0\x0B");
        if (preg_match(
            '/[^a-z0-9\/' . preg_quote($this->helper('slugs')::CHARS) . ']/i',
            $q
        )) {
            return null;
        }
        $id = md5(serialize(['locate', $q, $slugs]));
        if ($recache || !isset($this->readCache[$id])) {
            $search = $this->factory()->search();
            $qids = [$q];
            if ($slugs) {
                foreach ($this->helper('slugs')->nouns($q) as $sid) {
                    $qids[] = $sid;
                }
                $qids = array_unique($qids);
            }
            $qids = array_map([$search, 'quote'], $qids);
            $search->where('${dso.id} in (' . implode(",", $qids) . ')');
            $result = $search->execute();
            if ($result) {
                $this->readCache[$id] = $result;
            } else {
                $this->readCache[$id] = [];
            }
        }
        return $this->readCache[$id];
    }

    public function read(string $q = null, bool $slugs = true, bool $recache = false)
    {
        if (!$q) {
            return null;
        }
        $q = trim($q, "/ \t\n\r\0\x0B");
        if (preg_match(
            '/[^a-z0-9\/' . preg_quote($this->helper('slugs')::CHARS) . ']/i',
            $q
        )) {
            return null;
        }
        $id = md5(serialize(['read', $q, $slugs]));
        if ($recache || !isset($this->readCache[$id])) {
            $search = $this->factory()->search();
            $qids = [$q];
            if ($slugs) {
                foreach ($this->helper('slugs')->nouns($q) as $sid) {
                    $qids[] = $sid;
                }
                $qids = array_unique($qids);
            }
            $qids = array_map([$search, 'quote'], $qids);
            $search->where('${dso.id} in (' . implode(",", $qids) . ')');
            $result = @array_shift($search->execute());
            if ($result) {
                $this->readCache[$id] = $result;
            } else {
                $this->readCache[$id] = false;
            }
        }
        return $this->readCache[$id];
    }

    public function fullMunge(PackageInterface $package)
    {
        try {
            foreach ($this->config['fullmunge'] as $name) {
                $this->munge($package, $name);
            }
        } catch (\Exception $e) {
            echo "<div class='notification notification-error'>";
            echo "An unhandled exception occurred";
            echo "</div>";
            $package->saveLog('CMS::fullMunge: ' . $e->getMessage(), LogHelper::CRITICAL);
        }
    }

    public function munge(PackageInterface $package, string $name = 'build')
    {
        $p = $this->package;
        $start = microtime(true);
        $this->log('beginning munging with ' . $name);
        $package->cms($this);
        $this->package = $package;
        $this->munger($name)->munge($package);
        $this->package = $p;
        $this->log('finished munging with ' . $name . ' in ' . round((microtime(true) - $start) * 1000) . 'ms');
    }

    public function initializeMungers()
    {
        foreach ($this->config['mungers'] as $treeName => $mungers) {
            // sort by weight option
            uasort(
                $mungers,
                function ($a, $b) {
                    if (@$a['weight'] == @$b['weight']) {
                        return 0;
                    }
                    return (@$a['weight'] < @$b['weight']) ? -1 : 1;
                }
            );
            //place mungers into tree
            $tree = new MungerTree($treeName);
            foreach ($mungers as $name => $conf) {
                if (!is_array($conf)) {
                    throw new \Exception('Munger configuration should be an array: "' . $conf . '"');
                }
                $munger = new $conf['class']($name);
                $tree->add($munger, $name);
            }
            // set munger into CMS
            $this->munger($treeName, $tree);
        }
    }

    public function log(string $message = null): array
    {
        if ($message) {
            $this->log[] = round((microtime(true) - $this->start) * 1000) . ': ' . $message;
        }
        return $this->log;
    }

    public function allHelpers($includeUninstantiated = true)
    {
        if ($includeUninstantiated) {
            $helpers = array_keys($this->config['helpers.classes']);
        } else {
            $helpers = [];
        }
        $helpers = $helpers + $this->helpers;
        $helpers = array_unique($helpers);
        return $helpers;
    }

    public function helper(string $name, HelperInterface $set = null): ?HelperInterface
    {
        if ($set) {
            $this->helpers[] = $name;
            $this->helpers = array_unique($this->helpers);
            $this->log('Setting helper ' . $name . ': ' . get_class($set));
        } elseif (!$this->valueFunction('helper/' . $name)) {
            if ($class = $this->config['helpers.classes.' . $name]) {
                $this->log('Instantiating helper ' . $name);
                @$this->helper($name, new $class($this));
            }
        }
        return $this->valueFunction('helper/' . $name, $set);
    }

    public function munger(string $name = 'build', MungerInterface $set = null): ?MungerInterface
    {
        if ($set) {
            $set->name($name);
            $this->log('Setting munger ' . $name . ': ' . get_class($set));
            if (method_exists($set, 'cms')) {
                $set->cms($this);
            }
        }
        return $this->valueFunction('munger/' . $name, $set);
    }

    public function driver(string $name = 'default', AbstractDriver $set = null): ?AbstractDriver
    {
        if ($set) {
            $this->log('Setting driver ' . $name . ': ' . get_class($set));
            if (method_exists($set, 'cms')) {
                $set->cms($this);
            }
        }
        return $this->valueFunction('driver/' . $name, $set);
    }

    public function pdo(string $name = 'default', \PDO $set = null): ?\PDO
    {
        if ($set) {
            $this->log('Setting PDO ' . $name);
        }
        return $this->valueFunction('pdo/' . $name, $set);
    }

    public function factory(string $name = 'content', Factory $set = null): ?Factory
    {
        if ($set) {
            $this->log('Setting factory ' . $name . ': ' . get_class($set));
            if (method_exists($set, 'cms')) {
                $set->cms($this);
            }
            $set->name($name);
            $this->config->push('factories', $name);
        }
        return $this->valueFunction('factory/' . $name, $set);
    }

    public function allCaches($includeUninstantiated = true)
    {
        if ($includeUninstantiated) {
            $caches = array_keys($this->config['cache.adapters']);
        } else {
            $caches = [];
        }
        $caches = $caches + $this->caches;
        $caches = array_unique($caches);
        return $caches;
    }

    public function cache(?string $name = 'default', TagAwareAdapterInterface $set = null): ?TagAwareAdapterInterface
    {
        if ($set) {
            $this->caches[] = $name;
            $this->caches = array_unique($this->caches);
            $this->log('Setting cache ' . $name . ': ' . get_class($set));
        } elseif (!$this->valueFunction('cache/' . $name)) {
            if ($conf = $this->config['cache.adapters.' . $name]) {
                $this->log('Instantiating cache ' . $name);
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
        return $this->valueFunction('cache/' . $name, $set);
    }

    public function invalidateCache($dso_id)
    {
        if ($dso_id instanceof Noun) {
            $dso_id = $dso_id['dso.id'];
        }
        $this->log('invalidating cache for ' . $dso_id);
        $this->cache()->invalidateTags([$dso_id]);
    }

    protected function valueFunction(string $name, $value = null, $default = null)
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
