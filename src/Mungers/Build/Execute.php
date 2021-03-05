<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers\Build;

use Digraph\Mungers\AbstractMunger;

class Execute extends AbstractMunger
{
    const CACHE_ENABLED = true;
    protected $package;

    public function arg(string $name)
    {
        return $this->package["url.args.$name"];
    }

    public function argObject(string $name)
    {
        if (!($id = $this->arg($name))) {
            return null;
        }
        return $this->package->cms()->read($id);
    }

    protected function doMunge($package)
    {
        try {
            if ($package->noun() && $package['response.status'] == 200) {
                $package->merge(
                    [
                        'page_name' => $package->noun()->name($package->url()['verb']),
                        'page_title' => $package->noun()->title($package->url()['verb']),
                    ],
                    'fields',
                    true
                );
            }
            $this->package = $package;
            $this->execute();
        } catch (\Throwable $e) {
            @ob_end_clean();
            $package->error(500, get_class($e) . ": " . $e->getMessage() . ": " . $e->getFile() . ": " . $e->getLine());
            $package->set('error.trace', $e->getTrace());
        }
    }

    /**
     * Execution happens in its own function, just to isolate it
     * and make accidental side-effects harder.
     */
    protected function execute()
    {
        ob_start();
        // before hooks
        $this->nounHooks('first');
        $this->nounHooks('before');
        // include main file
        $this->includeFile($this->package['response.handler.file']);
        // after hooks
        $this->nounHooks('after');
        $this->nounHooks('last');
        // grab output buffer and return
        $this->package['response.content'] = ob_get_contents();
        ob_end_clean();
    }

    protected function nounHooks($hookName)
    {
        $noun = $this->package['noun.dso.type'] ?? $this->package['url.noun'];
        $verb = $this->package['url.verb'];
        foreach ($this->package->cms()->helper('routing')->allHookFiles($noun, $verb . '_' . $hookName . '.php') as $file) {
            $this->includeFile($file['file']);
        }
    }

    /**
     * There's a weird hack here because I, the developer, deploy this thing
     * in an environment where VERY occasionally included files will appear
     * as non-existant, and when included they appear as empty at the time.
     *
     * So to keep things working in that very odd circumstance we just wait
     * a second and try again.
     *
     * @param string $file
     * @param integer $depth
     * @return void
     */
    protected function includeFile(?string $file, $depth = 0)
    {
        // return on empty files
        if (!$file) {
            return;
        }
        // check file exists
        if (!file_exists($file)) {
            if ($depth == 5) {
                throw new \Exception("Error including file '" . $file . "' after trying $depth times");

            }
            sleep(1);
            $this->includeFile($file, $depth + 1);
            return;
        }
        // include file
        $package = $this->package;
        $cms = $package->cms();
        include $file;
    }

    protected function factory(string $name = 'content')
    {
        return $this->package->cms()->factory($name);
    }

    protected function helper(string $name)
    {
        return $this->package->cms()->helper($name);
    }

    protected function cms()
    {
        return $this->package->cms();
    }

    protected function url($noun, $verb, $args = [])
    {
        return $this->package->cms()->helper('urls')->url($noun, $verb, $args);
    }

    protected function doConstruct($name)
    {
    }
}
