<?php

namespace DigraphCMS\Plugins;

abstract class AbstractPlugin
{
    abstract function mediaFolders(): array;
    abstract function routeFolders(): array;
    abstract function templateFolders(): array;
    abstract function phinxFolders(): array;
    abstract function registered();

    function name(): string
    {
        return implode(
            '.',
            array_slice(
                explode('\\', static::class),
                -2
            )
        );
    }
}
