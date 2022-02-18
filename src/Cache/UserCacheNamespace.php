<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Session\Session;

class UserCacheNamespace extends CacheNamespace
{
    public function __construct(string $name)
    {
        parent::__construct("user/" . (Session::user() ?? 'guest') . "/" . $name);
    }
}
