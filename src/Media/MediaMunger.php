<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Mungers\AbstractMunger;

class MediaMunger extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        $m = $package->cms()->helper('media');
        if ($f = $m->get($package->url())) {
            $package->log('media located: '.$f['path']);
            //set package to output this file
            if ($f['content'] !== null) {
                $package['response.content'] = $f['content'];
                unset($package['response.readfile']);
            } else {
                $package['response.readfile'] = $f['path'];
                unset($package['response.content']);
            }
            $package['response.mime'] = $f['mime'];
            $package['response.filename'] = $f['filename'];
            //skip everything up until rendering
            $package->skipGlob('build**');
            $package->skipGlob('error**');
            $package->skipGlob('template**');
        }
    }

    protected function doConstruct($name)
    {
    }
}