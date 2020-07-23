<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Permissions;

use Digraph\Mungers\AbstractMunger;

class PermissionsMunger extends AbstractMunger
{
    protected function doMunge($package)
    {
        $helper = $package->cms()->helper('permissions');
        if (!$helper->checkUrl($package->url())) {
            $package->error(403);
        }
    }

    protected function doConstruct($name)
    {
    }
}
