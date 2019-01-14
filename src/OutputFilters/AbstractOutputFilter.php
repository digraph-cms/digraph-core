<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\OutputFilters;

use Digraph\Helpers\AbstractHelper;
use Digraph\Mungers\PackageInterface;

abstract class AbstractOutputFilter
{
    protected $cms;

    public function __construct(&$cms)
    {
        $this->cms = $cms;
    }

    public function filterPackage(&$package)
    {
        $this->doFilterPackage($package);
    }

    public function preFilterPackage(&$package)
    {
        $this->doPreFilterPackage($package);
    }
}
