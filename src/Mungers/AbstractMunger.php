<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

abstract class AbstractMunger implements MungerInterface
{
    protected $parent;
    protected $name;

    abstract protected function doMunge(&$package);
    abstract protected function doConstruct($name);

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
            $this->doMunge($package);
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
