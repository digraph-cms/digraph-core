<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Route extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        $noun = $package['url.noun'];
        $verb = $package['url.verb'];
        $args = $package['url.args'];
        if ($object = $package->noun()) {
            $proper = true;
            $noun = $package->noun()['dso.id'];
            $type = $package->noun()['dso.type'];
        } else {
            $proper = false;
            $type = $noun;
        }
        //route with file handlers
        $handler = $package->cms()->helper('routing')->file($type, $proper, $verb.'.php');
        if (!$handler) {
            $package->error(404, 'No route handler found');
            return;
        }
        $package['response.handler'] = $handler;
    }

    protected function doConstruct($name)
    {
    }
}
