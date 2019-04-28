<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Setup;

use Digraph\Mungers\AbstractMunger;

class ParseUrl extends AbstractMunger
{
    const CACHE_ENABLED = true;

    protected function doMunge(&$package)
    {
        //parse URL
        if (!($parsed = $package->cms()->helper('urls')->parse($package['request.url']))) {
            $package->error(404, 'Couldn\'t parse URL');
            return;
        }
        //save parsed url into package
        $package->url($parsed);
        //redirect if parsed URL doesn't match original request
        $url = $package->url()->string();
        $actual = $package->cms()->config['url.protocol'].$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        if ($url != $actual) {
            $package->redirect($url);
        }
    }

    protected function doConstruct($name)
    {
    }
}
