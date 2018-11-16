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

    public function children()
    {
        $search = $this->factory->search();
        $exclusions = '';
        $params = [':pattern'=>'%|'.$this['dso.id'].'|%'];
        $i = 0;
        foreach ($this->factory->cms()->config['excluded_child_types'] as $type => $value) {
            if ($value) {
                $i++;
                $exclusions .= ' AND ${dso.type} <> :type_'.$i;
                $params[':type_'.$i] = $type;
            }
        }
        $search->where('${digraph.parents_string} LIKE :pattern'.$exclusions);
        return $search->execute($params);
    }

    public function name($verb=null)
    {
        if ($this->get('digraph.name')) {
            return $this->get('digraph.name');
        }
        return $this->get('dso.type').' '.$this->get('dso.id');
    }

    public function title($verb=null)
    {
        if ($this->get('digraph.title')) {
            return $this->get('digraph.title');
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
