<?php

namespace DigraphCMS\Plugins;

use DigraphCMS\Initialization\InitializationState;

abstract class AbstractInitializedPlugin extends AbstractPlugin
{
    abstract function initialize_preCache(InitializationState $state);
    abstract function initialize_postCache(InitializationState $state);
}
