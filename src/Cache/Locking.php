<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\DB\DB;

class Locking
{
    /** @var LockingDriver|null */
    protected static $driver;

    public static function share(string $name, bool $blocking = false, int $ttl = 5): ?int
    {
        while (!($id = static::driver()->getSharedLock($name, $ttl))) {
            if ($blocking) usleep(random_int(0, 100));
            else return null;
        }
        return $id;
    }

    public static function lock(string $name, bool $blocking = false, int $ttl = 5): ?int
    {
        while (!($id = static::driver()->getExclusiveLock($name, $ttl))) {
            if ($blocking) usleep(random_int(0, 100));
            else return null;
        }
        return $id;
    }

    public static function release(int $id): void
    {
        static::driver()->release($id);
    }

    protected static function driver(): LockingDriver
    {
        if (!static::$driver) {
            $class = Config::get('locking.driver')
                ?? DB::driver() == 'sqlite'
                ? LockingDriverNull::class
                : LockingDriverDB::class;
            static::$driver = new $class;
        }
        return static::$driver;
    }
}
