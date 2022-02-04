<?php

namespace DigraphCMS\Initialization;

interface InitializedClassInterface
{
    public static function initialize_preCache(InitializationState $state);
    public static function initialize_postCache(InitializationState $state);
}
