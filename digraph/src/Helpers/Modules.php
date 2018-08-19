<?php
/* Digraph CMS | https://github.com/digraphcms/digraph | MIT License */
namespace Digraph\CMS\Helpers;

use Digraph\CMS\Helpers\AbstractHelper;
use Digraph\Config\Config;

class Modules extends AbstractHelper
{
    public function initialize()
    {
        $this->cms->log('ModuleManager initializing');
        $paths = $this->cms->config['modules.paths'];
        ksort($paths);
        foreach ($paths as $path) {
            $this->loadModuleDirectory($path);
        }
    }

    public function loadModuleDirectory($path)
    {
        foreach (glob($path.'/*/module.yaml') as $module) {
            $this->loadModule($module);
        }
    }

    public function loadModule($module)
    {
        $this->cms->log('ModuleManager: loading '.$module);
        $config = new Config();
        $config->readFile($module);
        $config->merge([
            'module.path' => dirname($module),
            'module.namespace' => '\\Digraph\\Modules\\${module.name}'
        ]);
        //unset module portion and merge everything else back into main CMS config
        $config = $config->get();
        unset($config['module']);
        $this->cms->config->merge($config, null);
    }
}
