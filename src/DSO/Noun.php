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
        if ($this->get('digraph.body')) {
            return $this->get('digraph.body');
        }
        return $this->name();
    }

    public function url(string $verb=null, array $args=null, bool $canonical=false)
    {
        $url = $this->factory->cms()->helper('urls')->url();
        $url['canonicalnoun'] = $this->get('dso.id');
        if ($this->get('digraph.slug') && !$canonical) {
            $url['noun'] = $this->get('digraph.slug');
        } else {
            $url['noun'] = $this->get('dso.id');
        }
        if ($verb) {
            $url['verb'] = $verb;
        }
        if ($args) {
            $url['args'] = $args;
        }
        $url['text'] = $this->urlText($verb, $args);
        $url['dso'] = $this['dso.id'];
        return $url;
    }

    public function urlText($verb, $args)
    {
        return $this->name();
    }
}
