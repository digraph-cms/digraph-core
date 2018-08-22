<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\Helpers\AbstractHelper;
use Flatrr\Config\Config;

class Modules extends AbstractHelper
{
    protected $autoloader;

    public function initialize()
    {
        $this->cms->log('ModuleManager initializing');
        $this->autoloader = new Modules\Autoloader();
        $this->autoloader->register();
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
            'module.name' => basename(dirname($module)),
            'module.path' => dirname($module),
            'module.namespace' => '\\Digraph\\Modules\\${module.name}'
        ]);
        /*
        Automatically add default paths to config if they exists
         */
        // src: register with autoloader
        if (is_dir($config['module.path'].'/src')) {
            $this->autoloader->addNamespace(
                $config['module.namespace'],
                $config['module.path'].'/src'
            );
        }
        // routes: add to routes config
        if (is_dir($config['module.path'].'/routes')) {
            $config['routing.paths.'.$config['module.name']] = $config['module.path'].'/routes';
        }
        // templates: add to templates config
        if (is_dir($config['module.path'].'/templates')) {
            $config['templates.paths.'.$config['module.name']] = $config['module.path'].'/templates';
        }
        // media: add to media config
        if (is_dir($config['module.path'].'/media')) {
            $config['media.paths.'.$config['module.name']] = $config['module.path'].'/media';
        }
        /*
        Unset module portion of config and merge what remains back into main config
         */
        $config = $config->get();
        unset($config['module']);
        $this->cms->config->merge($config, null);
    }
}
