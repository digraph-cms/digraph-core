<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Render;

use Digraph\Mungers\AbstractMunger;

class Output extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        /*
        if there's a response.readfile field, this means the response is asking
        to have that file passed directly through via readfile()
         */
        if ($package['response.readfile']) {
            //allow config to skip sending content length, because it doesn't
            //work right on all servers
            if ($package->cms()->config['send-content-length-header']) {
                //send a content length header so that download progress works
                $size = filesize($package['response.readfile']);
                @header('Content-Length:'.$size);
            }
            //send file to browser
            readfile($package['response.readfile']);
            return;
        }
        /*
        note that we output the raw version of response.content
        This is to avoid leaking information or producing unexpected results,
        since Packages are made from SelfReferencingFlatArray
         */
        echo $package->get('response.content', true);
    }

    protected function doConstruct($name)
    {
    }
}
