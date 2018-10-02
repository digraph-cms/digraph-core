<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules;

/**
 * ModuleInterface is for classes that can be used to load modules from a PHP
 * class rather than a module file/directory. This is useful for making modules
 * that can be installed with Composer.
 *
 * Configuring such a module is done by adding it to
 */
abstract class AbstractModule implements ModuleInterface
{
    /**
     * Gets the path to a module.yaml file to load this module from. In this
     * implementation it's expected to be in the same directory as the source
     * file of the module's class.
     */
    public function getYAMLPath() : string
    {
        $class = get_called_class();
        $reflector = new \ReflectionClass($class);
        if (!$reflector->getFileName()) {
            throw new \Exception("Module class $class couldn't be matched to a source file. Class modules must exist in the filesystem somewhere.");
        }
        $path = dirname($reflector->getFileName()).'/module.yaml';
        if (!is_file($path)) {
            throw new \Exception("Couldn't locate a module.yaml file in the same directory as $class. AbstractModule expects it to be there.");
        }
        return $path;
    }

    /**
     * Gets the default config from which modules should begin their initialize
     * and loading processes
     */
    public function getConfig() : array
    {
        $namespace = get_called_class();
        $namespace = '\\'.preg_replace('/\\\[^\\\]+$/', '', $namespace);
        return [
            'namespace' => $namespace
        ];
    }
}
