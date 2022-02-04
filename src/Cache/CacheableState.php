<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use Flatrr\SelfReferencingFlatArray;

class CacheableState extends SelfReferencingFlatArray
{
    protected $configData;
    protected $configUpdated = false;

    public function __construct()
    {
        $this->configData = clone Config::data();
    }

    public function updatedConfig(): ?SelfReferencingFlatArray
    {
        return $this->configUpdated ? $this->configData : null;
    }

    public function config(string $key, $value = null)
    {
        if ($value !== null) {
            $this->configData[$key] = $value;
            $this->configUpdated = true;
        }
        return $this->configData[$key];
    }

    public function mergeConfig(array $data, $overwrite = false)
    {
        $this->configData->merge($data, null, $overwrite);
        $this->configUpdated = true;
    }
}
