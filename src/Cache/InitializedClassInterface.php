<?php

namespace DigraphCMS\Cache;

interface InitializedClassInterface
{
    public static function initialize_preCache(CacheableState $state): void;
    public static function initialize_postCache(CacheableState $state): void;
}
