<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Error;

use Digraph\Mungers\AbstractMunger;

class Error extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        $status = $package['response.status'];
        if ($status != 200) {
            $package['fields.page_name'] = 'Error '.$status;
            $handlers = [
                $status,
                floor($status/100).'xx',
                'xxx'
            ];
            foreach ($handlers as $name) {
                if ($file = $package->cms()->helper('routing')->file('@error', true, $name.'.php')) {
                    ob_start();
                    include($file['file']);
                    $package['response.content'] = ob_get_contents();
                    ob_end_clean();
                    return;
                }
            }
        }
    }

    protected function doConstruct($name)
    {
    }
}
