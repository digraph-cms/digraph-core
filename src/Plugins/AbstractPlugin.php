<?php

namespace DigraphCMS\Plugins;

abstract class AbstractPlugin
{
    abstract function initialConfig(): array;
    abstract function mediaFolders(): array;
    abstract function routeFolders(): array;
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
