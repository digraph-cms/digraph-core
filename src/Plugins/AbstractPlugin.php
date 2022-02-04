<?php

namespace DigraphCMS\Plugins;

abstract class AbstractPlugin
{
    abstract function mediaFolders(): array;
    abstract function routeFolders(): array;
    abstract function templateFolders(): array;
    abstract function phinxFolders(): array;

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
