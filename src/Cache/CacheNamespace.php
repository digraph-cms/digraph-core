<?php

namespace DigraphCMS\Cache;

class CacheNamespace
{
    protected $name, $ttl, $staleTTL;

    public function __construct(string $name, int $ttl = null, int $staleTTL = null)
    {
        $this->name = $name;
        $this->ttl = $ttl;
        $this->staleTTL = $staleTTL;
    }

    /**
     * Works the same as get(), but the callback is required and if the value is
     * not already cached then the value will *not* be computed now. Instead a
     * deferred execution job will be created to generate the value, and until
     * it executes calls for the given $name will return null.
     *
     * @param string $name
     * @param callable|null $callback
     * @param integer|null $ttl
     * @param integer|null $staleTTL
     * @return mixed
     */
    public function getDeferred(string $name, callable $callback = null, int $ttl = null, int $staleTTL = null)
    {
        return Cache::getDeferred(
            $this->name . '/' . $name,
            $callback,
            $ttl ?? $this->ttl,
            $staleTTL ?? $this->staleTTL
        );
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
        return Cache::get($this->name . '/' . $name, $callback, $ttl ?? $this->ttl);
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
        return Cache::set($this->name . '/' . $name, $value, $ttl ?? $this->ttl);
    }
}
