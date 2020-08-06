<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Logging;

use Digraph\Mungers\AbstractMunger;

class LogMunger extends AbstractMunger
{
    protected function doMunge($package)
    {
        // debug logging
        if ($package->cms()->config['logging.debug']) {
            if ($package['response.status'] != 200) {
                $package->saveLog('Debug: status '.$package['response.status'], 100);
            }
            if ($package->msElapsed() > 500) {
                $package->saveLog('Debug: slow execution', 200);
            }
        }
        // error logging
        if ($package['response.status'] == 500) {
            $package->saveLog(
                $package['error.message'] ?? 'unknown error',
                LogHelper::CRITICAL
            );
        }
    }

    protected function doConstruct($name)
    {
    }
}
