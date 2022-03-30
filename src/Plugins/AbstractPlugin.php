<?php

namespace DigraphCMS\Plugins;

use ReflectionClass;

abstract class AbstractPlugin
{

    function name(): string
    {
        return implode(
            '.',
            array_slice(
                explode('\\', static::class),
                -3,
                2
            )
        );
    }

    public function mediaFolders(): array
    {
        if (is_dir($this->path() . '/media')) {
            return [$this->path() . '/media'];
        } else {
            return [];
        }
    }

    public function routeFolders(): array
    {
        if (is_dir($this->path() . '/routes')) {
            return [$this->path() . '/routes'];
        } else {
            return [];
        }
    }

    public function templateFolders(): array
    {
        if (is_dir($this->path() . '/templates')) {
            return [$this->path() . '/templates'];
        } else {
            return [];
        }
    }

    public function phinxFolders(): array
    {
        if (is_dir($this->path() . '/phinx')) {
            return [$this->path() . '/phinx'];
        } else {
            return [];
        }
    }

    public function path(): string
    {
        return dirname((new ReflectionClass(get_called_class()))->getFileName());
    }
}
