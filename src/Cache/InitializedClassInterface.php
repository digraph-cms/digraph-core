<?php

namespace DigraphCMS\Cache;

interface InitializedClassInterface
{
    public static function initialize_preCache(CacheableState $state);
    public static function initialize_postCache(CacheableState $state);
}
