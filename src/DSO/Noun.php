<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\DSO;
use Destructr\DSOFactoryInterface;

class Noun extends DSO implements NounInterface
{
    const FILESTORE = false;
    const ROUTING_NOUNS = [];

    public function __construct(array $data = null, DSOFactoryInterface &$factory = null)
    {
        parent::__construct($data, $factory);
        $this->merge($factory->cms()->config['defaultnoun']);
        $this->resetChanges();
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
        return $this->factory->cms()->helper('filters')->filterContentField(
            $this['digraph.body'],
            $this['dso.id']
        );
    }

    public function delete(bool $permanent=false) : bool
    {
        if (static::FILESTORE && $permanent) {
            exit("TODO: clear files when nouns are permanently deleted");
        }
        return parent::delete($permanent);
    }

    public function fileUrl($id=null, $args=[])
    {
        if ($id === null) {
            $fs = $this->factory->cms()->helper('filestore');
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
        return $this->factory->cms()->helper('permissions')->checkUrl($this->url('edit'));
    }

    public function parentUrl($verb='display')
    {
        if ($verb != 'display') {
            return $this->url();
        }
        if ($parent = @$this->parents()[0]) {
            return $parent->url();
        }
        return null;
    }

    public function addParent($dso)
    {
        $this->unshift('digraph.parents', $dso);
        $parents = [];
        foreach (array_unique($this['digraph.parents']) as $i) {
            $parents[] = $i;
        }
        unset($this['digraph.parents']);
        $this['digraph.parents'] = $parents;
        $this['digraph.parents_string'] = '|'.implode('|', $this['digraph.parents']).'|';
    }

    public function parents()
    {
        if (!$this['digraph.parents']) {
            return [];
        }
        $parents = [];
        foreach ($this['digraph.parents'] as $id) {
            $parents[] = $this->factory->cms()->read($id);
        }
        return $parents;
    }

    public function parent()
    {
        if (!$this['digraph.parents']) {
            return null;
        }
        return $this->factory->cms()->read($this['digraph.parents.0']);
    }

    public function children(string $sortRule = null)
    {
        /* set up search */
        $search = $this->factory->search();
        $exclusions = '';
        $params = [':pattern'=>'%|'.$this['dso.id'].'|%'];
        /* exclude types from config */
        $i = 0;
        foreach ($this->factory->cms()->config['excluded_child_types'] as $type => $value) {
            if ($value) {
                $i++;
                $exclusions .= ' AND ${dso.type} <> :type_'.$i;
                $params[':type_'.$i] = $type;
            }
        }
        /* main search, look using LIKE in digraph.parents_string */
        $search->where('${digraph.parents_string} LIKE :pattern'.$exclusions);
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
        $children = $search->execute($params);
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
                $children = $children+$manuallySorted;
            } else {
                $children = $manuallySorted+$children;
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
