<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\OutputFilters;

use Digraph\Mungers\AbstractMunger;
use Flatrr\FlatArray;

class OutputFilterMunger extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        //check if outputfilter is set, so we can avoid instantiating the helper
        //if it isn't actually needed
        if ($package['response.outputfilter']) {
            $package->cms()->helper('outputfilters')->filterPackage($package);
        }
    }

    protected function doConstruct($name)
    {
    }
}
