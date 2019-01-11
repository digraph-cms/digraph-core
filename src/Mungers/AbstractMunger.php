<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

abstract class AbstractMunger implements MungerInterface
{
    const CACHE_ENABLED = false;
    protected $parent;
    protected $name;

    abstract protected function doMunge(&$package);
    abstract protected function doConstruct($name);

    protected function cacheHash(&$package)
    {
        return $package->hash();
    }

    protected function doMunge_cached(&$package)
    {
        //if cache isn't enabled, just run doMunge
        if (!static::CACHE_ENABLED || !$package['response.cacheable']) {
            $this->doMunge($package);
            return;
        }
        //use cache if possible
        $cache = $package->cms()->cache($package->cms()->config['cache.mungercache.adapter']);
        $id = 'mungercache.'.md5(serialize([$this->cacheHash($package),get_called_class()]));
        if ($cache && $cache->hasItem($id)) {
            //load result from cache
            $start = microtime(true);
            $r = $cache->getItem($id)->get();
            $package->unserialize($r);
            $duration = 1000*(microtime(true)-$start);
            $package->log('mungercache: hit loaded in '.$duration.'ms');
        } else {
            $package->log('mungercache: running '.get_called_class());
            $start = microtime(true);
            $this->doMunge($package);
            $duration = 1000*(microtime(true)-$start);
            $package->log('mungercache: took '.$duration.'ms');
            if ($cache && $package['response.cacheable'] && $duration > $package->cms()->config['cache.mungercache.threshold']) {
                $package->log('mungercache: saving');
                $citem = $cache->getItem($id);
                $citem->expiresAfter($package['response.ttl']);
                if (!($serialized = $package->serialize())) {
                    $package->error(500, 'Failed to serialize package for cache');
                    return;
                }
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

    public function munge(PackageInterface &$package)
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

    public function &parent(MungerInterface &$parent = null) : ?MungerInterface
    {
        if ($parent !== null) {
            $this->parent = $parent;
        }
        return $this->parent;
    }
}
