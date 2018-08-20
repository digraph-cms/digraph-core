<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

class FunctionMunger extends AbstractMunger
{
    protected $fn;

    public function function($fn)
    {
        $this->fn = $fn;
    }

    protected function doMunge(&$package)
    {
        ($this->fn)($package);
    }
    protected function doConstruct($name)
    {
        //does nothing
    }
}
