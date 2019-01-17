<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\OutputFilters;

use Digraph\Helpers\AbstractHelper;
use Digraph\Mungers\PackageInterface;
use Digraph\Urls\Url;

class OutputFilterHelper extends AbstractHelper
{
    public function filterPackage(PackageInterface &$package)
    {
        if ($filter = $package['response.outputfilter']) {
            if ($filter = $this->getFilter($filter)) {
                return $filter->filterPackage($package);
            }
        }
    }

    public function preFilterPackage(PackageInterface &$package)
    {
        if ($filter = $package['response.outputfilter']) {
            if ($filter = $this->getFilter($filter)) {
                return $filter->preFilterPackage($package);
            }
        }
    }

    public function getFilter($filter)
    {
        if ($class = $this->cms->config['outputfilters.'.$filter]) {
            return new $class($this->cms);
        }
    }
}
