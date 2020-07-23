<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

abstract class AbstractMunger implements MungerInterface
{
    const CACHE_ENABLED = false;
    const CACHE_ON_KEY = 'request.hash';
    protected $parent;
    protected $name;

    abstract protected function doMunge($package);
    abstract protected function doConstruct($name);

    protected function cacheHash($package)
    {
        return $package->hash(static::CACHE_ON_KEY);
    }

    protected function doMunge_cached($package)
    {
        //if cache isn't enabled, just run doMunge
        if (!static::CACHE_ENABLED || !$package['response.cacheable']) {
            if (!$package['response.cacheable']) {
                $package->log('mungercache: disabled by package.response.cacheable');
            }
            $this->doMunge($package);
            return;
        }
        //use cache if possible
        $cache = $package->cms()->cache($package->cms()->config['cache.mungercache.adapter']);
        $id = 'mungercache.'.md5(serialize([$this->cacheHash($package),get_called_class()]));
        $package->log('mungercache: id: '.$id);
        if ($cache && $cache->hasItem($id)) {
            //load result from cache
            $start = microtime(true);
            $r = $cache->getItem($id)->get();
            $package->unserialize($r);
            $duration = 1000*(microtime(true)-$start);
            $package->log('mungercache: hit loaded in '.$duration.'ms');
        } else {
            //execute and time how long it takes
            $package->log('mungercache: running '.get_called_class());
            $start = microtime(true);
            $this->doMunge($package);
            $duration = 1000*(microtime(true)-$start);
            $package->log('mungercache: took '.$duration.'ms');
            //check if this result should be cached
            if ($cache && $package['response.cacheable'] && $duration > $package->cms()->config['cache.mungercache.threshold']) {
                //set up cache item
                $package->log('mungercache: saving: '.$package['response.ttl']);
                $citem = $cache->getItem($id);
                $citem->expiresAfter($package['response.ttl']);
                //add cache tags from package
                if ($package['cachetags']) {
                    $citem->tag($package['cachetags']);
                    $package->log('mungercache: tagged '.implode(', ', $package['cachetags']));
                }
                //try to serialize package
                if (!($serialized = $package->serialize())) {
                    $package->error(500, 'Failed to serialize package for cache');
                    return;
                }
                //save into cache
                $citem->set($serialized);
                $cache->save($citem);
            }
        }
    }

    public function __construct(string $name, MungerInterface $parent = null)
    {
        $this->name($name);
        $this->parent($parent);
        $this->doConstruct($name, $parent);
    }

    public function munge(PackageInterface $package)
    {
        if ($package->skip($this)) {
            $package->log($this->name().': skipped');
        } else {
            $package->mungeStart($this);
            $this->doMunge_cached($package);
            $package->mungeFinished($this);
        }
    }

    protected function skip($package) : bool
    {
        return false;
    }

    public function name(string $name = null, bool $includeParent = true) : string
    {
        if ($name !== null) {
            $this->name = $name;
        }
        return ($includeParent?$this->parentNamePrefix():'').$this->name;
    }

    protected function parentNamePrefix() : string
    {
        if ($this->parent()) {
            return $this->parent()->name().'/';
        }
        return '';
    }

    public function parent(MungerInterface $parent = null) : ?MungerInterface
    {
        if ($parent !== null) {
            $this->parent = $parent;
        }
        return $this->parent;
    }
}
