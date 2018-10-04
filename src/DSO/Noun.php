<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\DSO;

use Destructr\DSO;
use Destructr\DSOFactoryInterface;

class Noun extends DSO implements NounInterface
{
    public function __construct(array $data = null, DSOFactoryInterface &$factory = null)
    {
        parent::__construct($data, $factory);
        $this->merge($factory->cms()->config['defaultnoun']);
        $this->resetChanges();
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

    public function children()
    {
        $search = $this->factory->search();
        $search->where('${digraph.parents_string} LIKE :pattern');
        return $search->execute([':pattern'=>'%|'.$this['dso.id'].'|%']);
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

    public function body()
    {
        if ($this->get('digraph.body.text')) {
            $text = $this->factory->cms()->helper('filters')->filterPreset(
                $this->get('digraph.body.text'),
                $this->get('digraph.body.filter')
            );
            return $text;
        }
        return '[no body content found]';
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
