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
interface ModuleInterface
{
    public function getYAMLPath() : string;
    public function getConfig() : array;
}
