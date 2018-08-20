<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Render;

use Digraph\Mungers\AbstractMunger;

class Output extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        /*
        note that we output the raw version of response.content
        This is to avoid leaking information or producing unexpected results,
        since Packages are made from SelfReferencingFlatArray
         */
        echo $package->get('response.content', true);
    }

    protected function doConstruct($name)
    {
    }
}
