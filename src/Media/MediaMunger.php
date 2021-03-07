<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Mungers\AbstractMunger;

class MediaMunger extends AbstractMunger
{
    protected function doMunge($package)
    {
        $f = $package->cms()->helper('media')
            ->get($package->url());
        if ($f !== null) {
            $package->log('media located: ' . $f['path']);
            //set package to redirect to static asset
            $package->redirect($f['url'], 301);
            //skip everything up until rendering
            $package->skipGlob('setup**');
            $package->skipGlob('build**');
            $package->skipGlob('error**');
            $package->skipGlob('template**');
        }
    }

    protected function doConstruct($name)
    {
    }
}
