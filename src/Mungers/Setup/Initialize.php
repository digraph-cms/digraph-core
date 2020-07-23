<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Setup;

use Digraph\Mungers\AbstractMunger;

class Initialize extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge($package)
    {
        //merged default settings into package
        $package->merge($package->cms()->config['package.defaults']);
        //save hash that can be used later for caching
        //NOTE: hash is also based off of the actual URL being used for access
        $package['request.actualurl'] = $package->cms()->config['url.protocol'].$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        $package['request.hash'] = $package->hash('request');
    }

    protected function doConstruct($name)
    {
    }
}
