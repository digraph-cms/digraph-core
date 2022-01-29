<?php

namespace DigraphCMS\Cache;

class StatelessOpCache extends OpCache
{
    public function __construct(string $dir, int $ttl)
    {
        $this->dir = $dir;
        $this->ttl = $ttl;
        $this->fuzz = $ttl;
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
    public function cache(string $name, callable $callback = null, int $ttl = null)
    {
        if ($this->exists($name) && !$this->expired($name)) {
            return $this->get($name);
        } elseif ($callback) {
            $this->set($name, call_user_func($callback), $ttl);
            return $this->get($name);
        } else {
            return null;
        }
    }
}
