<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\Cron\DeferredJob;

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
    public static function getDeferred(string $name, callable $callback = null, int $ttl = null, int $staleTTL = null)
    {
        $staleTTL = $staleTTL ?? 3600;
        $valueKey = $name . '/v';
        $staleKey = $name . '/s';
        $queueKey = $name . '/q';
        // if no callback, only return values
        if (!$callback) return static::get($staleKey) ?? static::get($valueKey);
        // return value if it is set
        return static::get($valueKey)
            // otherwise cached-create deferred job to run callback and set value
            ?? static::get(
                $queueKey,
                function () use ($valueKey, $staleKey, $callback, $ttl, $staleTTL) {
                    // create deferred job
                    new DeferredJob(
                        function () use ($valueKey, $staleKey, $callback, $ttl, $staleTTL) {
                            $value = call_user_func($callback);
                            static::set($valueKey, $value, $ttl);
                            static::set($staleKey, $value, $staleTTL);
                            return 'Set deferred value: ' . $valueKey;
                        },
                        'cache_deferred_values'
                    );
                    // return stale value, if it's still valid
                    return static::get($staleKey);
                },
                $ttl
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
    public static function get(string $name, callable $callback = null, int $ttl = null)
    {
        if (static::$driver->exists($name) && !static::$driver->expired($name)) {
            return static::$driver->get($name);
        } elseif ($callback) {
            static::$driver->set($name, $value = call_user_func($callback), $ttl);
            return $value;
        } else {
            return null;
        }
    }

    public static function invalidate(string $glob)
    {
        static::$driver->invalidate($glob);
    }
}
