<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use Flatrr\SelfReferencingFlatArray;

class CacheableState extends SelfReferencingFlatArray
{
    /** @var SelfReferencingFlatArray */
    protected $configData;
    /** @var bool */
    protected $configUpdated = false;

    public function __construct()
    {
        $this->configData = clone Config::data();
    }

    public function updatedConfig(): ?SelfReferencingFlatArray
    {
        return $this->configUpdated ? $this->configData : null;
    }

    public function config(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $this->configData[$key] = $value;
            $this->configUpdated = true;
        }
        return $this->configData[$key];
    }

    /**
     * @param array<mixed,mixed> $data
     * @param boolean $overwrite
     * @return void
     */
    public function mergeConfig(array $data, bool $overwrite = false)
    {
        $this->configData->merge($data, null, $overwrite);
        $this->configUpdated = true;
    }
}
