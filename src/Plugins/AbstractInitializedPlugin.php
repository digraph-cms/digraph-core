<?php

namespace DigraphCMS\Plugins;

use DigraphCMS\Cache\CacheableState;

abstract class AbstractInitializedPlugin extends AbstractPlugin
{
    abstract function initialize_preCache(CacheableState $state);
    abstract function initialize_postCache(CacheableState $state);
}
