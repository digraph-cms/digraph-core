<?php

namespace DigraphCMS\Initialization;

use DigraphCMS\Cache\StatelessOpCache;
use DigraphCMS\Config;

class Initializer
{
    /** @var StatelessOpCache */
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

    public static function run(string $key, $preCacheFn, $postCacheFn = null)
    {
        $state = new InitializationState();
        if (!static::$cache) {
            // just call sequentially if there is no cache configured
            call_user_func($preCacheFn, $state);
        } else {
            // otherwise get state from a cached run of $preCacheFn and then run $postCacheFn
            $state = static::$cache->cache($key, function () use ($preCacheFn, $state) {
                call_user_func($preCacheFn, $state);
                return $state;
            });
        }
        // run postCacheFn
        if ($postCacheFn) {
            call_user_func($postCacheFn, $state);
        }
        // apply config changes
        if ($state->updatedConfig()) {
            Config::data($state->updatedConfig());
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
