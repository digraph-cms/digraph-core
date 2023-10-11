<?php

namespace DigraphCMS\Cache;

use DigraphCMS\DB\DB;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\PdoStore;
use Symfony\Component\Lock\SharedLockInterface;
use Throwable;

class Locking
{
    /** @var array<string,SharedLockInterface> */
    protected static array $locks = [];

    public static function lock(string $name, bool $blocking = false, int $ttl = 30): bool
    {
        $lock = static::factory()->createLock($name, $ttl, true);
        while (true) {
            try {
                $lock->acquire();
                static::$locks[$name] = $lock;
                return true;
            } catch (Throwable) {
                if ($blocking) usleep(random_int(0, 100));
                else return false;
            }
        }
    }

    public static function release(string $name): void
    {
        if (isset(static::$locks[$name])) {
            static::$locks[$name]->release();
            unset(static::$locks[$name]);
        }
    }

    protected static function factory(): LockFactory
    {
        static $store;
        static $factory;
        $store = $store ?? new PdoStore(DB::pdo());
        $factory = $factory ?? new LockFactory($store);
        return $factory;
    }
}
