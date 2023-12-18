<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;
use Symfony\Component\Lock\Exception\LockAcquiringException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\PdoStore;
use Symfony\Component\Lock\SharedLockInterface;
use Symfony\Component\Lock\Store\FlockStore;

class Locking
{
    /** @var array<string,SharedLockInterface> */
    protected static array $locks = [];

    public static function lock(string $name, bool $blocking = false, int $ttl = 30): bool
    {
        $lock = static::factory()->createLock($name, $ttl, true, false);
        while (true) {
            try {
                $lock->acquire();
                static::$locks[$name] = $lock;
                return true;
            } catch (LockConflictedException) {
                if ($blocking) usleep(random_int(0, 100));
                else return false;
            } catch (LockAcquiringException) {
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
        if (!$store) {
            switch (Config::get('locking.storage')) {
                case 'db':
                    $store = new PdoStore(DB::pdo());
                    break;
                default:
                    $store = new FlockStore(Config::cachePath() . '/locking');
            }
        }
        $factory = $factory ?? new LockFactory($store);
        return $factory;
    }
}
