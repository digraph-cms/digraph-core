<?php

namespace DigraphCMS\Plugins;

use DigraphCMS\Config;

abstract class AbstractPlugin
{
    abstract function initialConfig(): array;
    abstract function isEventSubscriber(): bool;
    abstract function mediaFolders(): array;
    abstract function routeFolders(): array;
    abstract function phinxFolders(): array;
    abstract function postRegistrationCallback();

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    function name(): string
    {
        return $this->name;
    }

    function config(string $key)
    {
        return Config::get('plugins.' . $this->name . '.config.' . $key);
    }
}
