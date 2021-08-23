<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

abstract class AbstractCacheDriver
{
    protected $dir;

    abstract public function exists(string $name): bool;
    abstract public function expired(string $name): bool;
    abstract public function get(string $name, callable $callback = null, int $ttl = null);
    abstract public function set(string $name, $value, int $ttl = null);

    public function __construct()
    {
        $this->dir = Config::get('cache.path');
    }
}
