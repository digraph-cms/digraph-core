<?php

namespace DigraphCMS\Cache;

class CacheNamespace
{
    protected $name, $ttl;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Attempt to get a value, optionally with a read-through callback that will
     * be executed and used to set the cache item's value for the given TTL if
     * it does not exist or is expired.
     *
     * @param string $name
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public function get(string $name, callable $callback = null, int $ttl = null)
    {
        Cache::get($this->name . '/' . $name, $callback, $ttl);
    }

    public function exists(string $name): bool
    {
        return Cache::exists($this->name . '/' . $name);
    }

    public function expired(string $name): bool
    {
        return Cache::expired($this->name . '/' . $name);
    }

    public function set(string $name, $value, int $ttl = null)
    {
        return Cache::set($this->name . '/' . $name, $value, $ttl);
    }
}
