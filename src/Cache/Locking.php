<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

class Locking
{
    /** @var LockingDriver|null */
    protected static $driver;

    public static function lock(string $name, bool $blocking = false, int $ttl = 30): bool
    {
        while (!($id = static::driver()->lock($name, $ttl))) {
            if ($blocking) usleep(random_int(0, 100));
            else return false;
        }
        return true;
    }

    public static function release(string $id): void
    {
        static::driver()->release($id);
    }

    protected static function driver(): LockingDriver
    {
        if (!static::$driver) {
            $class = Config::get('locking.driver')
                ?? LockingDriverFiles::class;
            static::$driver = new $class;
        }
        return static::$driver;
    }
}
