<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

class MungerTree extends AbstractMunger
{
    protected $nodes = [];

    public function add(MungerInterface &$munger)
    {
        $munger->parent($this);
        $this->nodes[$munger->name()] = [
            'munger' => $munger,
            'pre' => [],
            'post' => []
        ];
    }

    public function pre($location, MungerInterface &$munger)
    {
        $this->hook('pre', $location, $munger);
    }

    public function post($location, MungerInterface &$munger)
    {
        $this->hook('post', $location, $munger);
    }

    protected function hook($loc, $name, MungerInterface &$munger)
    {
        if ($name instanceof MungerInterface) {
            $name = $name->name();
        }
        if (isset($this->nodes[$name])) {
            $munger->parent($this->nodes[$name]['munger']);
            $this->nodes[$name][$loc][$munger->name()] = $munger;
        }
    }

    protected function doMunge(&$package)
    {
        foreach ($this->nodes as $node) {
            foreach ($node['pre'] as $hook) {
                $hook->munge($package);
            }
            $node['munger']->munge($package);
            foreach ($node['post'] as $hook) {
                $hook->munge($package);
            }
        }
    }

    protected function doConstruct($name)
    {
        //does nothing
    }
}
