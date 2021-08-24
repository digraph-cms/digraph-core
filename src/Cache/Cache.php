<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

Cache::_init();

/**
 * Because Digraph is a majestic monolith, its caching strategy is based on
 * using the local filesystem. For this reason, it currently only ships with a
 * built-in cache driver that uses a var_export() and include based caching
 * system inspired by the Laravel opcache.
 * 
 * On systems with Zend OPcache enabled this should make reading from this cache
 * about as fast as is conceivably possible. Basically as fast as constructing
 * the given data natively.
 * 
 * Even without OPcache it should still be very fast, as there's no actual
 * serialization or unserialization happening. It's just executing PHP code to
 * generate the value.
 */
class Cache
{
    /** @var AbstractCacheDriver */
    protected static $driver;

    public static function _init()
    {
        $class = Config::get('cache.driver');
        static::$driver = new $class;
    }

    public static function exists(string $name): bool
    {
        return static::$driver->exists($name);
    }

    public static function expired(string $name): bool
    {
        return static::$driver->expired($name);
    }

    public static function set(string $name, $value, int $ttl = null)
    {
        return static::$driver->set($name, $value, $ttl);
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
    public static function get(string $name, callable $callback = null, int $ttl = null)
    {
        if (static::$driver->exists($name) && !static::$driver->expired($name)) {
            return static::$driver->get($name);
        } elseif ($callback) {
            static::$driver->set($name, call_user_func($callback), $ttl);
            return static::$driver->get($name);
        } else {
            return null;
        }
    }

    public static function invalidate(string $glob)
    {
        static::$driver->invalidate($glob);
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
