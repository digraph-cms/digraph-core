<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Execute extends AbstractMunger
{
    protected $package;

    protected function doMunge(&$package)
    {
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
