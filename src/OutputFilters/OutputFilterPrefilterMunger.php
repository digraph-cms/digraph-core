<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\OutputFilters;

use Digraph\Mungers\AbstractMunger;

class OutputFilterPrefilterMunger extends AbstractMunger
{
    protected function doMunge($package)
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
            //offer outputfilter a chance to mess with the package before templating
            if (!$package->cms()->helper('outputfilters')->preFilterPackage($package)) {
                //filter can return false to convert this into a 404 error
                $package->error(404, 'Output filter refused to process this');
                $package['response.outputfilter'] = false;
                return;
            }
            //pull override template from folder named by outputfilter
            $t = $package->cms()->helper('templates');
            $template = $package->template();
            $template = "$filter/$template";
            if ($t->exists($template)) {
                $package->template($template);
            }
        }
    }

    protected function doConstruct($name)
    {
    }
}
