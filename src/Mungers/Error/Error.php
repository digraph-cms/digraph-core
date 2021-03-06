<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Mungers\Error;

use Digraph\Mungers\Build\Execute;

class Error extends Execute
{
    protected function doMunge($package)
    {
        $status = $package['response.status'];
        if ($status != 200) {
            //reset package settings
            unset($package['response.outputfilter']);
            //set up error settings
            $package['response.mime'] = 'text/html';
            $package['fields.page_name'] = 'Error ' . $status;
            $package['fields.page_title'] = 'Error ' . $status;
            $package['url.text'] = 'Error ' . $status;
            $package['response.status'] = $status;
            $handlers = [
                "$status",
                floor($status / 100) . 'xx',
                'xxx',
            ];
            unset($package['response.handler']);
            foreach ($handlers as $name) {
                if ($package['response.handler'] = $package->cms()->helper('routing')->file('@error', false, $name . '.php')) {
                    parent::doMunge($package);
                    return;
                }
            }
            $package['response.content'] = 'Error ' . $status . ': Additionally no handler could be found';
        }
    }

    protected function doConstruct($name)
    {
    }
}
