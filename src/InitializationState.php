<?php

namespace DigraphCMS;

use Flatrr\SelfReferencingFlatArray;

/**
 * Provides a cacheable object for storing initialization state
 * when Digraph::initialize is called with a cache directory.
 * 
 * Plugins that need to do expensive initialization should do it 
 * when the onDigraphInitialized_precache event fires, and use
 * the InitializationState provided to cache the results.
 * 
 * Cached InitializationState objects will also be provided in
 * the event onDigraphInitialized_postcache, and can be used to
 * retrieve initialization state and put it where it needs to go.
 */
class InitializationState
{
    protected $configData;
    protected $data = [];

    public function __construct(SelfReferencingFlatArray $configData)
    {
        $this->configData = $configData;
    }

    public function configData(): SelfReferencingFlatArray
    {
        return $this->configData;
    }

    public function get(string $name)
    {
        return @$this->data[$name];
    }

    public function set(string $name, $value)
    {
        $this->data[$name] = $value;
    }
}
