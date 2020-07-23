<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

class NullMunger extends AbstractMunger
{
    protected function doMunge($package)
    {
        //does nothing
    }
    protected function doConstruct($name)
    {
        //does nothing
    }
}
