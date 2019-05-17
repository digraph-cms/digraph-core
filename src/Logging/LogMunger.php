<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Logging;

use Digraph\Mungers\AbstractMunger;

class LogMunger extends AbstractMunger
{
    protected function doMunge(&$package)
    {
        if ($this->cms->config['logging.debug'] && $package['response.status'] != 200) {
            $package->saveLog('Status '.$package['response.status'], 100);
        }
    }

    protected function doConstruct($name)
    {
    }
}
