<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */

namespace Digraph\FastPHP;

use Digraph\Mungers\AbstractMunger;

function execute_file(string $file, $package)
{
    include $file;
}

class FastPHPMunger extends AbstractMunger
{
    protected function doConstruct($name)
    {
    }

    /**
     * Undocumented function
     *
     * @param Digraph\Mungers\Package $package
     * @return void
     */
    protected function doMunge($package)
    {
        if ($package['url.noun'] == '_fastphp') {
            $handler = $package->cms()->helper('routing')->file('_fastphp', false, $package['url.verb'] . '.php');
            if (!$handler) {
                $package->error(404, 'No route handler found');
                return;
            }
            // set MIME/filename from verb
            if (preg_match('/\.[a-z0-9]+$/', $package['url.verb'])) {
                header('Content-Type: ' . $package->cms()->helper('media')->mime($package['url.verb']));
                header('Content-Disposition: filename="' . $package['url.verb'] . '"');
            } else {
                header('Content-Disposition: filename="' . $package['url.verb'] . '.html"');
            }
            execute_file($handler['file'], $package);
            exit();
        }
    }
}
