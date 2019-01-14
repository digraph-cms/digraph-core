<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class ParseUrl extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        //parse URL
        if (!($parsed = $package->cms()->helper('urls')->parse($package['request.url']))) {
            $package->error(404, 'Couldn\'t parse URL');
            return;
        }
        //save parsed url into package
        $package->url($parsed);
    }

    protected function doConstruct($name)
    {
    }
}
