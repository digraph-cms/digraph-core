<?php

namespace DigraphCMS\Cache;

class CacheNamespace
{
    protected $name,$ttl;

    public function __construct(string $name, int $ttl=null)
    {
        $this->name = $name;
    }

    public function get(string $name, callable $callback = null, int $ttl = null) {
        Cache::get($this->name.'/'.$name,$callback);
    }
}
