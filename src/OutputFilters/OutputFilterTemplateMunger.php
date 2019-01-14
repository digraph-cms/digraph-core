<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\OutputFilters;

use Digraph\Mungers\AbstractMunger;

class OutputFilterTemplateMunger extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        if ($filter = @$_GET['outputfilter']) {
            if (isset($package->cms()->config['outputfilters.'.$filter])) {
                $package['response.outputfilter'] = $filter;
            } else {
                $package->error(404, 'Output filter not found');
                return;
            }
        }
        //mess with templates per outputfilter
        if ($filter = $package['response.outputfilter']) {
            //pull override template from folder named by outputfilter
            $t = $package->cms()->helper('templates');
            $template = $package->template();
            $template = "$filter/$template";
            if ($t->exists($template)) {
                $package->template($template);
            }
            //offer outputfilter a chance to mess with the package before templating
            $package->cms()->helper('outputfilters')->templatePackage($package);
        }
    }

    protected function doConstruct($name)
    {
    }
}
