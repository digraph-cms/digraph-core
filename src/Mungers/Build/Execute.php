<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Execute extends AbstractMunger
{
    protected $package;

    protected function doMunge(&$package)
    {
        if ($package->noun()) {
            $package->merge(
                [
                    'page_name' => $package->noun()->name($package->url()['verb']),
                    'page_title' => $package->noun()->title($package->url()['verb'])
                ],
                'fields',
                true
            );
        }
        $this->package = $package;
        $this->execute();
    }

    /**
     * Execution happens in its own function, just to isolate it
     * and make accidental side-effects harder.
     */
    protected function execute()
    {
        if (file_exists($this->package['response.handler.file'])) {
            ob_start();
            $package = $this->package;
            include $this->package['response.handler.file'];
            $this->package['response.content'] = ob_get_contents();
            ob_end_clean();
        } else {
            $this->package->error(500, 'Handler file doesn\'t exist');
        }
    }

    protected function &factory(string $name='content')
    {
        return $this->package->cms()->factory($name);
    }

    protected function &helper(string $name)
    {
        return $this->package->cms()->helper($name);
    }

    protected function &cms()
    {
        return $this->package->cms();
    }

    protected function doConstruct($name)
    {
    }
}
