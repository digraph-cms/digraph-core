<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Initialize extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        //parse url
        $package->merge($package->cms()->config['package.defaults']);
        if (!($parsed = $package->cms()->helper('urls')->parse($package['request.url.original'], true))) {
            $package->error(404, 'Couldn\'t parse URL');
            return;
        }
        //save hash that can be used later for caching
        $package['request.hash'] = $package->hash('request');
        //save parsed url into package
        $package->url($parsed);
    }

    protected function doConstruct($name)
    {
    }
}
