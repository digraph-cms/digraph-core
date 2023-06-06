<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

abstract class AbstractCacheDriver
{
    /** @var string */
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
     * @return static
     */
    abstract public function set(string $name, mixed $value, int $ttl = null): static;
    
    /**
     * Check whether a given cache item exists
     * 
     * @param string $name
     * @return bool
     */
    abstract public function exists(string $name): bool;

    /**
     * Check whether a given cache item is expired, return true for items that
     * do not exist.
     * 
     * @param string $name
     * @return bool
     */
    abstract public function expired(string $name): bool;

    /**
     * Get the value of a given cache item, if it exists, otherwise null.
     * 
     * @param string $name
     * @return mixed
     */
    abstract public function get(string $name): mixed;

    /**
     * Invalidate a given cache item, which may be many items as specified 
     * using a glob.
     * 
     * @param string $glob
     * @return static
     */
    abstract public function invalidate(string $glob): static;

    /**
     * Summary of __construct
     */
    public function __construct()
    {
        $this->dir = Config::get('cache.path');
    }

    /**
     * Ensure that a given cache name is valid. Throws an exception if it is not.
     * 
     * @param string $name
     * @throws \Exception
     * @return void
     */
    public static function checkName(string $name): void
    {
        if (!preg_match('/[a-z0-9\-_]+(\/[a-z0-9\-_]+)*/', $name)) {
            throw new \Exception("Invalid cache name. Item names must be valid directory paths consisting of only lower-case alphanumerics, dashes, and underscores.");
        }
    }

    /**
     * Ensure that a given cache glob is valid. Throws an exception if it is not.
     * 
     * @param string $glob
     * @throws \Exception
     * @return void
     */
    public static function checkGlob(string $glob): void
    {
        if (!preg_match('/[a-z0-9\-_\*\?\[\]]+(\/[a-z0-9\-_\*\?\[\]]+)*/', $glob)) {
            throw new \Exception("Invalid cache glob. Item globs must be valid directory paths consisting of only lower-case alphanumerics, dashes, underscores, and glob characters.");
        }
    }
}