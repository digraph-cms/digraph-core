<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\DSO;
use Destructr\DSOFactoryInterface;

class Noun extends DSO implements NounInterface
{
    const SLUG_ENABLED = false;
    const PUBLISH_CONTROL = false;
    const FILESTORE = false;
    const ROUTING_NOUNS = [];

    public function __construct(array $data = null, DSOFactoryInterface &$factory = null)
    {
        parent::__construct($data, $factory);
        $this->merge($factory->cms()->config['defaultnoun']);
        $this->resetChanges();
    }

    public function cms()
    {
        return $this->factory->cms();
    }

    public function invalidateCache($recurseUp=null, $recurseDown=null)
    {
        $invalidated = [];
        //invalidate my own cache
        $this->cms()->invalidateCache($this['dso.id']);
        $invalidated[] = $this['dso.id'];
        //recurse up through parents
        if ($recurseUp && $recurseUp !== $this['dso.id']) {
            if ($recurseUp === true) {
                $recurseUp = $this['dso.id'];
            }
            foreach ($this->parents() as $parent) {
                $invalidated = array_merge($invalidated, $parent->invalidateCache($recurseUp));
            }
        }
        //recurse down through children
        if ($recurseDown && $recurseDown !== $this['dso.id']) {
            if ($recurseDown === true) {
                $recurseDown = $this['dso.id'];
            }
            foreach ($this->children() as $child) {
                $invalidated = array_merge($invalidated, $child->invalidateCache(null, $recurseDown));
            }
        }
        //return list of invalidated caches
        return $invalidated;
    }

    public function formMap(string $action) : array
    {
        $map = [];
        if (!static::PUBLISH_CONTROL) {
            $map['900_digraph_published'] = false;
        }
        if (!static::SLUG_ENABLED) {
            $map['100_digraph_slug'] = false;
        }
        return $map;
    }

    public function add() : bool
    {
        $this->invalidateCache(true);
        return parent::add();
    }

    public function update(bool $sneaky = false) : bool
    {
        $this->invalidateCache(true, true);
        return parent::update($sneaky);
    }

    public function template($verb=null)
    {
        return null;
    }

    public function body()
    {
        if (!$this['digraph.body']) {
            return null;
        }
        return $this->cms()->helper('filters')->filterContentField(
            $this['digraph.body'],
            $this['dso.id']
        );
    }

    public function delete(bool $permanent=false) : bool
    {
        if (static::FILESTORE && $permanent) {
            exit("TODO: clear files when nouns are permanently deleted");
        }
        $this->invalidateCache();
        return parent::delete($permanent);
    }

    public function fileUrl($id=null, $args=[])
    {
        if ($id === null) {
            $fs = $this->cms()->helper('filestore');
            $files = $fs->list($this, static::FILESTORE_PATH);
            if (!$files) {
                return null;
            }
            $f = array_pop($files);
            $id = $f->uniqid();
        }
        $args['f'] = $id;
        return $this->url(
            'file',
            $args
        );
    }

    public function actions($links)
    {
        if ($this->children()) {
            $links['ordering'] = '!id/order';
        }
        return $links;
    }

    public function isPublished()
    {
        //no information
        if (!$this['digraph.published']) {
            return true;
        }
        //forced unpublished
        if ($this['digraph.published.force'] == 'unpublished') {
            return false;
        }
        //forced published
        if ($this['digraph.published.force'] == 'published') {
            return true;
        }
        //check start date
        if ($this['digraph.published.start'] && time() < $this['digraph.published.start']) {
            return false;
        }
        //check end date
        if ($this['digraph.published.end'] && time() >= $this['digraph.published.end']) {
            return false;
        }
        //default is to return true
        return true;
    }

    public function isEditable()
    {
        return $this->cms()->helper('permissions')->checkUrl($this->url('edit'));
    }

    public function parentUrl($verb='display')
    {
        if ($verb != 'display') {
            return $this->url();
        }
        if ($parent = $this->parent()) {
            return $parent->url();
        }
        return null;
    }

    public function addParent($pid)
    {
        $this->cms()->helper('edges')->create($pid, $this['dso.id']);
    }

    public function addChild($cid)
    {
        $this->cms()->helper('edges')->create($this['dso.id'], $cid);
    }

    public function parents()
    {
        $pids = $this->cms()->helper('edges')->parents($this['dso.id']);
        if (!$pids) {
            //short-circuit if edge helper has no parents for this noun
            return [];
        }
        $pids = '${dso.id} in (\''.implode('\',\'', $pids).'\')';
        $search = $this->factory->search();
        $search->where($pids);
        return $search->execute();
    }

    public function parent()
    {
        $pids = $this->cms()->helper('edges')->parents($this['dso.id']);
        foreach ($pids as $pid) {
            if ($parent = $this->cms()->read($pid)) {
                return $parent;
            }
        }
        return null;
    }

    public function children(string $sortRule = null, $includeAll = false)
    {
        /* pull list of child IDs from edge helper, create IN clause to get them */
        $cids = $this->cms()->helper('edges')->children($this['dso.id']);
        if (!$cids) {
            //short-circuit if edge helper has no children for this noun
            return [];
        }
        $cids = '${dso.id} in (\''.implode('\',\'', $cids).'\')';
        /* set up search */
        $search = $this->factory->search();
        $exclusions = '';
        /* exclude types from config */
        if (!$includeAll) {
            if ($this->factory->cms()->config['excluded_child_types']) {
                $e = [];
                foreach ($this->factory->cms()->config['excluded_child_types'] as $key => $value) {
                    if ($value) {
                        $e[] = "'$key'";
                    }
                }
                $exclusions .= ' AND ${dso.type} NOT IN ('.implode(',', $e).')';
            }
        }
        /* main search */
        //put CIDs clause first, so that the rest is operating on as small a
        //result set as possible
        // var_dump($cid.$exclusions);
        // exit();
        $search->where($cids.$exclusions);
        /* if no sort rule, pull it from our own config */
        if (!$sortRule) {
            $sortRule = $this['digraph.order.mode'];
        }
        /* pull sort rule based on manual sort rule if necessary -- this allows
        ordering to be different depending on whether unsorted items are going
        at the top or bottom */
        $manualSort = false;
        if ($sortRule == 'manual' && $this['digraph.order.mode'] == 'manual') {
            $manualSort = true;
            if ($this['digraph.order.unsorted'] == 'before') {
                $unsorted = 'before';
                $sortRule = 'manual_before';
            } else {
                $unsorted = 'after';
                $sortRule = 'manual_after';
            }
        }
        /* sorting rules */
        $cms = $this->factory->cms();
        if (!$sortRule || !$cms->config["child_sorting.$sortRule"]) {
            $sortRule = 'default';
        }
        $rule = $cms->config["child_sorting.$sortRule"];
        $search->order($rule);
        /* execute */
        $children = $search->execute();
        /* manually sort */
        if ($manualSort) {
            // add all manually-specified ids to $manuallySorted, removing from
            // $children as we go
            $manuallySorted = [];
            foreach ($this['digraph.order.manual'] as $ov) {
                foreach ($children as $ck => $cv) {
                    if ($cv['dso.id'] == $ov) {
                        $manuallySorted[] = $cv;
                        unset($children[$ck]);
                    }
                }
            }
            // append/prepend $children to $manuallySorted
            if ($unsorted == 'before') {
                $children = array_merge($children, $manuallySorted);
            } else {
                $children = array_merge($manuallySorted, $children);
            }
        }
        /* return */
        return $children;
    }

    public function name($verb=null)
    {
        if ($this->get('digraph.name')) {
            return $this->factory->cms()->helper('filters')->sanitize($this->get('digraph.name'));
        }
        return $this->get('dso.type').' '.$this->get('dso.id');
    }

    public function title($verb=null)
    {
        if ($this->get('digraph.title')) {
            return $this->factory->cms()->helper('filters')->sanitize($this->get('digraph.title'));
        }
        return $this->name($verb);
    }

    public function link(string $text=null, string $verb=null, array $args=null, bool $canonical=false)
    {
        if (method_exists($this, 'tagLink')) {
            $args = [];
            return $this->tagLink($args);
        }
        return $this->url($verb, $args, $canonical)->html($text);
    }

    public function url(string $verb=null, array $args=null, bool $canonical=false)
    {
        if (!$verb) {
            $verb = 'display';
        }
        $noun = null;
        if ($this->get('digraph.slug') && !$canonical) {
            $noun = $this->get('digraph.slug');
        } else {
            $noun = $this->get('dso.id');
        }
        if ($args) {
            $args = $args;
        }
        $url = $this->factory->cms()->helper('urls')->url($noun, $verb, $args);
        $url['object'] = $this['dso.id'];
        $url['canonicalnoun'] = $this->get('dso.id');
        return $this->factory->cms()->helper('urls')->addText($url);
    }
}
