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
        $lock = static::$locks[$name]
            ?? static::$locks[$name] = static::factory()->createLock($name, $ttl, false);
        if ($blocking) {
            $lock->acquire(true);
        }else {
            try {
                $lock->acquire(false);
            } catch (LockAcquiringException $e) {
                return false;
            } catch (LockConflictedException $e) {
                return false;
            }
        }
        return true;
    }

    public static function release(string $name): void
    {
        if ($lock = static::$locks[$name] ?? null) {
            $lock->release();
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
