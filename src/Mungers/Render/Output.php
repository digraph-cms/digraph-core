<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Render;

use Digraph\Mungers\AbstractMunger;

class Output extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        switch ($package['response.outputmode']) {
            /* Send the contents of a file somewhere to the browser */
            case 'readfile':
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
            /* Send package's binaryContent() value to the browser */
            case 'binary':
                echo $package->binaryContent();
                return;
            /* Default case is to just send response.content */
            default:
                echo $package->get('response.content', true);
                return;
        }
    }

    protected function doConstruct($name)
    {
    }
}
