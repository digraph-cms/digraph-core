<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Route extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        $handlers = [];
        $opts = isset($package['located-options'])?$package['located-options']:[];
        $args = $package['url.args'];
        foreach ($opts as list($noun, $verb, $dso)) {
            $dso = $package->cms()->read($dso, false);
            if (!$verb) {
                $verb = 'display';
            }
            //first check for file handler
            $handler = $package->cms()->helper('routing')->file($dso['dso.type'], true, $verb.'.php');
            if ($handler) {
                $handlers[] = [
                    $handler,
                    $dso
                ];
            }
        }
        //if no handler was found, try to find a common noun/verb pair
        if (!$handlers) {
            $pathString = trim($package->url()->pathString(), "/ \t\n\r\0\x0B");
            $pathString = explode('/', $pathString);
            if (count($pathString) <= 2) {
                $type = $pathString[0];
                $verb = isset($pathString[1])?$pathString[1]:'display';
                $handler = $package->cms()->helper('routing')->file($type, false, $verb.'.php');
                if ($handler) {
                    $handlers[] = [
                        $handler,
                        null
                    ];
                }
            }
        }
        //put handlers into package
        if (!$handlers) {
            $package->error(404, 'No route handler found');
        } elseif (count($handlers) === 1) {
            $handler = array_pop($handlers);
            $package['response.handler'] = $handler[0];
            $package->noun($handler[1]);
        } else {
            echo "TODO: make 300 pages in Route munger";
            var_dump($handlers);
            exit();
        }
        // $noun = $package['url.noun'];
        // $verb = $package['url.verb'];
        // $args = $package['url.args'];
        // if ($object = $package->noun()) {
        //     $proper = true;
        //     $noun = $package->noun()['dso.id'];
        //     $type = $package->noun()['dso.type'];
        // } else {
        //     $proper = false;
        //     $type = $noun;
        // }
        // //route with file handlers
        // $handler = $package->cms()->helper('routing')->file($type, $proper, $verb.'.php');
        // if (!$handler) {
        //     $package->error(404, 'No route handler found');
        //     return;
        // }
        // $package['response.handler'] = $handler;
    }

    protected function doConstruct($name)
    {
    }
}
