<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Mungers\Render;

use Digraph\Mungers\AbstractMunger;

class Headers extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        /*
        output headers
         */
        //status
        http_response_code($package['response.status']);
        //everything else
        foreach ($package['response.headers'] as $name => $value) {
            if ($value === false) {
                // $package->log('removing header: '.$name);
                header_remove($name);
            } elseif ($value === true) {
                // $package->log('setting header: '.$name);
                header($name);
            } else {
                // $package->log('setting header: '.$name.': '.$value);
                @header("$name: $value");
            }
        }
        //skip output if we're redirecting
        if ($package['response.redirect']) {
            $package->skipGlob('render**');
        }
    }

    protected function doConstruct($name)
    {
    }
}
