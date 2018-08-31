<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Error;

use Digraph\Mungers\Build\Execute;

class Error extends Execute
{
    protected function doMunge(&$package)
    {
        $status = $package['response.status'];
        if ($status != 200) {
            $package['fields.page_name'] = 'Error '.$status;
            $handlers = [
                "$status",
                floor($status/100).'xx',
                'xxx'
            ];
            foreach ($handlers as $name) {
                if ($package['response.handler'] = $package->cms()->helper('routing')->file('@error', true, $name.'.php')) {
                    parent::doMunge($package);
                    return;
                }
            }
            $package['response.content'] = 'Error '.$status.': Additionally no handler could be found';
        }
    }

    protected function doConstruct($name)
    {
    }
}
