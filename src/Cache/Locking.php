<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\SharedLockStoreInterface;
use Symfony\Component\Lock\Store\FlockStore;

class Locking
{
    protected static $locks = [];

    public static function share(string $name, bool $blocking = false): bool
    {
        $lock = static::lockObject($name);
        if (!$blocking) {
            return $lock->acquireRead();
        } else {
            while (!$lock->acquireRead()) {
                usleep(random_int(0, 100));
            }
            return true;
        }
    }

    public static function lock(string $name, bool $blocking = false): bool
    {
        $lock = static::lockObject($name);
        if (!$blocking) {
            return $lock->acquire();
        } else {
            while (!$lock->acquire()) {
                usleep(random_int(0, 100));
            }
            return true;
        }
    }

    public static function release(string $name)
    {
        if (static::$locks[$name]) {
            static::$locks[$name]->release();
        }
    }

    public static function lockObject(string $name, int $ttl = 300): SharedLockInterface
    {
        return static::$locks[$name]
            ?? static::$locks[$name] = static::factory()->createLock($name, $ttl);
    }

    protected static function store(): SharedLockStoreInterface
    {
        static $store;
        if (!$store) {
            $store = new FlockStore(Config::get('cache.path') . '/locking');
        }
        return $store;
    }

    protected static function factory(): LockFactory
    {
        static $factory;
        return $factory
            ?? $factory = new LockFactory(static::store());
    }
}
