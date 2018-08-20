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
        $this->merge($factory->cms()->config['noun.defaults']);
        $this->resetChanges();
    }

    public function name()
    {
        if ($this->get('digraph.name')) {
            return $this->get('digraph.name');
        }
        return $this->get('dso.type').' '.$this->get('dso.id');
    }

    public function title()
    {
        if ($this->get('digraph.title')) {
            return $this->get('digraph.title');
        }
        return $this->name();
    }

    public function url(string $verb=null, array $args=null, bool $canonical=false)
    {
        $url = $this->factory->cms()->helper('urls')->url();
        $url['canonicalnoun'] = $this->get('dso.id');
        $url->dso($this);
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
        return $url;
    }
}
