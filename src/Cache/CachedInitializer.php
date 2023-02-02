<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Cache\StatelessOpCache;
use DigraphCMS\Config;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URLs;

class CachedInitializer
{
    /** @var StatelessOpCache|null */
    protected static $cache;

    protected static $cacheDir = null;
    protected static $ttl = 60;

    public static function configureCache(string $cacheDir = null, int $ttl = 60)
    {
        static::$cacheDir = $cacheDir;
        static::$ttl = $ttl;
        if ($cacheDir && $ttl) {
            static::$cache = new StatelessOpCache($cacheDir, $ttl);
        } else {
            static::$cache = null;
        }
    }

    /**
     * Helper function for setting config, so that anything that needs to be done
     * after a config change can be called automatically.
     *
     * @param callable $fn
     * @param integer|null $overrideTTL
     * @return void
     */
    public static function config(callable $fn, int $overrideTTL = null)
    {
        static::run('config', $fn, null, $overrideTTL);
        URLs::_init($_SERVER);
        Dispatcher::dispatchEvent('onConfigInitialized');
    }

    /**
     * Run two functions sequentially, with a shared state object that is cached
     * after the first one runs. Will be cached under the given key, using the
     * settings from configureCache()
     *
     * @param string $key
     * @param callable $preCacheFn
     * @param callable|null $postCacheFn
     * @param integer|null $overrideTTL
     * @return void
     */
    public static function run(string $key, $preCacheFn, $postCacheFn = null, int $overrideTTL = null)
    {
        static $configUpdated = false;
        if ($configUpdated || !static::$cache) {
            // just call sequentially if there is no cache configured, or if a
            // cache item has updated config prior to this one
            $state = new CacheableState();
            call_user_func($preCacheFn, $state);
        } else {
            // otherwise get state from a cached run of $preCacheFn and then run $postCacheFn
            $state = static::$cache->cache(
                $key,
                function () use ($preCacheFn, &$configUpdated) {
                    $state = new CacheableState();
                    call_user_func($preCacheFn, $state);
                    if ($state->updatedConfig()) {
                        $configUpdated = true;
                    }
                    return $state;
                },
                $overrideTTL
            );
        }
        // apply config changes
        if ($state->updatedConfig()) {
            Config::data($state->updatedConfig());
        }
        // run postCacheFn
        if ($postCacheFn) {
            call_user_func($postCacheFn, $state);
        }
    }

    public static function runClass(string $class)
    {
        $key = preg_replace('/[^a-z0-9\-\_]+/', '/', strtolower($class)) . '_' . md5($class);
        static::run(
            $key,
            [$class, 'initialize_preCache'],
            method_exists($class, 'initialize_postCache') ? [$class, 'initialize_postCache'] : null
        );
    }
}
