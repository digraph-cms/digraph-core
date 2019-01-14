<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Initialize extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        //merged default settings into package
        $package->merge($package->cms()->config['package.defaults']);
        //save hash that can be used later for caching
        $package['request.hash'] = $package->hash('request');
    }

    protected function doConstruct($name)
    {
    }
}
