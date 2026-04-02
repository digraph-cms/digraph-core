<?php

namespace DigraphCMS\Cache;

class CacheNamespace
{

    /** @var string */
    protected $name;

    /** @var int|null */
    protected       $ttl, $staleTTL;

    public function __construct(string $name, int|null $ttl = null, int|null $staleTTL = null)
    {
        $this->name = $name;
        $this->ttl = $ttl;
        $this->staleTTL = $staleTTL;
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
    public function get(string $name, callable|null $callback = null, int|null $ttl = null): mixed
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

    public function set(string $name, mixed $value, int|null $ttl = null): mixed
    {
        return Cache::set($this->name . '/' . $name, $value, $ttl ?? $this->ttl);
    }

}
