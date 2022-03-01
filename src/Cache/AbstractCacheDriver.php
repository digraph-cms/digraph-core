<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

abstract class AbstractCacheDriver
{
    protected $dir;

    /**
     * Set a given cache entry, using either a straight value or
     * a callback. The TTL is the amount of time in seconds before
     * this entry will be considered expired.
     * 
     * TTL of 0 will prevent any checks or writing to the cache.
     * 
     * TTL of -1 will never expire.
     * 
     * The set or cached value will be returned, so that getting
     * and setting a cached value can be handled in one function
     * call using a callable value.
     *
     * @param string $name
     * @param mixed|callable $value
     * @param integer|null $ttl
     * @return mixed
     */
    abstract public function set(string $name, $value, int $ttl = null);
    abstract public function exists(string $name): bool;
    abstract public function expired(string $name): bool;
    abstract public function get(string $name);
    abstract public function invalidate(string $glob);

    public function __construct()
    {
        $this->dir = Config::get('cache.path');
    }

    public static function checkName(string $name)
    {
        if (!preg_match('/[a-z0-9\-_]+(\/[a-z0-9\-_]+)*/', $name)) {
            throw new \Exception("Invalid cache name. Item names must be valid directory paths consisting of only lower-case alphanumerics, dashes, and underscores.");
        }
    }

    public static function checkGlob(string $glob)
    {
        if (!preg_match('/[a-z0-9\-_\*\?\[\]]+(\/[a-z0-9\-_\*\?\[\]]+)*/', $glob)) {
            throw new \Exception("Invalid cache glob. Item globs must be valid directory paths consisting of only lower-case alphanumerics, dashes, underscores, and glob characters.");
        }
    }
}
