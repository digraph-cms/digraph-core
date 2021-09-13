<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */

namespace Digraph\Modules;

use Destructr\DriverFactory;
use Digraph\DSO\DigraphFactory;
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
            } elseif ($type == 'composer-dir') {
                $this->loadModuleDirectory($source, true);
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

    public function loadModuleDirectory($path, bool $noAutoloader = false)
    {
        foreach (glob($path . '/*/module.{yaml,json}', GLOB_BRACE) as $module) {
            $this->loadModule($module, [], $noAutoloader);
        }
    }

    public function loadModule($module, array $config = [], bool $noAutoloader = false)
    {
        $this->cms->log('ModuleManager: loading ' . $module);
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
        // can be skipped by setting $noAutoloader to true
        // in config, this is done by loading a module directory with
        // the prefix "composer-dir" instead of "dir"
        if (!$noAutoloader) {
            if (is_dir($config['module.path'] . '/src')) {
                $this->cms->log('autoloader: ' . $config['module.namespace'] . ': ' . $config['module.path'] . '/src');
                $this->autoloader->addNamespace(
                    $config['module.namespace'],
                    $config['module.path'] . '/src'
                );
            }
        }
        // routes: add to routes config
        if (is_dir($config['module.path'] . '/routes')) {
            $config['routing.paths.' . $config['module.name']] = $config['module.path'] . '/routes';
        }
        // templates: add to templates config
        if (is_dir($config['module.path'] . '/templates')) {
            $config['templates.paths.' . $config['module.name']] = $config['module.path'] . '/templates';
        }
        // media: add to media config
        if (is_dir($config['module.path'] . '/media')) {
            $config['media.paths.' . $config['module.name']] = $config['module.path'] . '/media';
        }
        /*
        Set up sqlite factories using options in module.sqlite
         */
        if ($config['module.factories']) {
            //set up drivers -- if a driver by this name already exists, do nothing
            //this way sites can override the drivers for modules if necessary
            if ($config['module.factories.drivers']) {
                foreach ($config['module.factories.drivers'] as $name) {
                    //skip creating drivers that exist
                    if ($this->cms->driver($name)) {
                        continue;
                    }
                    //instantiate this driver
                    $path = $this->cms->config['paths.storage'] . '/' . $name . '.sqlite';
                    $driver = DriverFactory::factory('sqlite:' . $path);
                    $this->cms->driver($name, $driver);
                }
            }
            //Set up factories the same way. Allow them to skip if a factory by
            //the requested name exists so that sites can override them.
            if ($config['module.factories.factories']) {
                foreach ($config['module.factories.factories'] as $name => list($driver, $table, $class)) {
                    //skip creating factories that exists
                    if ($this->cms->factory($name)) {
                        continue;
                    }
                    //instantiate this factory
                    $class = $class ? $class : DigraphFactory::class;
                    $factory = new $class(
                        $this->cms->driver($driver),
                        $table
                    );
                    $this->cms->factory($name, $factory);
                }
            }
        }
        /*
        Unset module portion of config and merge what remains back into main config
         */
        $config = $config->get();
        unset($config['module']);
        $this->cms->config->merge($config, null, true);
        $this->cms->config->push('modules.loaded', $module);
    }
}
