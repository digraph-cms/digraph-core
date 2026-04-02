<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

class StatelessOpCache extends OpCache
{

    public function __construct(string $dir, int $ttl)
    {
        $this->dir = $dir . '/' . Config::envPrefix();
        $this->ttl = $ttl;
        $this->fuzz = max(60, $ttl);
    }

    /**
     * Attempt to get a value, optionally with a read-through callback that will
     * be executed and used to set the cache item's value for the given TTL if
     * it does not exist or is expired.
     */
    public function cache(string $name, callable|null $callback = null, int|null $ttl = null): mixed
    {
        if ($this->exists($name) && !$this->expired($name)) {
            return $this->get($name);
        }
        elseif ($callback) {
            $this->set($name, call_user_func($callback), $ttl);
            return $this->get($name);
        }
        else {
            return null;
        }
    }

}
