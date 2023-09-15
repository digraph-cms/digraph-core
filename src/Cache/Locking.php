<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;

class Locking
{
    /** @var LockingDriver|null */
    protected static $driver;

    public static function lock(string $name, bool $blocking = false, int $ttl = 30): ?int
    {
        while (!($id = static::driver()->lock($name, $ttl))) {
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
                ?? LockingDriverFiles::class;
            static::$driver = new $class;
        }
        return static::$driver;
    }
}
