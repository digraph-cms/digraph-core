<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules;

use Digraph\Helpers\AbstractHelper;
use Flatrr\Config\Config;

class ModuleHelper extends AbstractHelper
{
    protected $autoloader;

    public function initialize()
    {
        $this->cms->log('ModuleManager initializing');
        $this->autoloader = new Autoloader();
        $this->autoloader->register();
        $sources = $this->cms->config['modules.sources'];
        ksort($sources);
        foreach ($sources as $source) {
            list($type, $source) = explode(' ', $source, 2);
            if ($type == 'class') {
                $this->loadModuleClass($source);
            } elseif ($type == 'dir') {
                $this->loadModuleDirectory($source);
            } elseif ($type == 'file') {
                $this->loadModule($source);
            } else {
                throw new \Exception("Unknown module type: $type");
            }
        }
    }

    public function loadModuleClass($class)
    {
        $module = new $class();
        $this->loadModule($module->getYAMLPath(), $module->getConfig());
    }

    public function loadModuleDirectory($path)
    {
        foreach (glob($path.'/*/module.yaml') as $module) {
            $this->loadModule($module);
        }
    }

    public function loadModule($module, array $config=[])
    {
        $this->cms->log('ModuleManager: loading '.$module);
        $config = new Config($config);
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
        // media: add to media config
        if (is_dir($config['module.path'].'/languages')) {
            $config['languages.paths.'.$config['module.name']] = $config['module.path'].'/languages';
        }
        /*
        Unset module portion of config and merge what remains back into main config
         */
        $config = $config->get();
        unset($config['module']);
        $this->cms->config->merge($config, null);
    }
}
