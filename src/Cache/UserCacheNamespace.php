<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Session\Session;

class UserCacheNamespace extends CacheNamespace
{
    public function __construct(string $name, int $ttl = null)
    {
        parent::__construct("user/" . Session::uuid() . "/" . $name, $ttl);
    }
}
