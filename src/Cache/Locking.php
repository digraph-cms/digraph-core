<?php

namespace DigraphCMS\Cache;

use DigraphCMS\DB\DB;

class Locking
{
    public static function share(string $name, bool $blocking = false, int $ttl = 5): ?int
    {
        while (!($id = static::getSharedLock($name, $ttl))) {
            if ($blocking) usleep(random_int(0, 100));
            else return null;
        }
        return $id;
    }

    public static function lock(string $name, bool $blocking = false, int $ttl = 5): ?int
    {
        while (!($id = static::getExclusiveLock($name, $ttl))) {
            if ($blocking) usleep(random_int(0, 100));
            else return null;
        }
        return $id;
    }

    public static function release(int $id)
    {
        DB::query()
            ->delete('locking', $id)
            ->execute();
    }

    protected static function getExclusiveLock(string $name, int $ttl): ?int
    {
        DB::beginTransaction();
        $query = DB::query()->from('locking')
            ->where('`name` = ?', [$name])
            ->where('`expires` > ?', [time()])
            ->limit(1);
        $id = null;
        if (!$query->count()) {
            // no exclusive locks exist, save this shared lock
            $id = DB::query()->insertInto(
                'locking',
                [
                    '`name`' => $name,
                    '`expires`' => time() + $ttl,
                    '`exclusive`' => true
                ]
            )->execute();
        }
        DB::commit();
        return $id ? $id : null;
    }

    protected static function getSharedLock(string $name, int $ttl): ?int
    {
        DB::beginTransaction();
        $query = DB::query()->from('locking')
            ->where('`name` = ?', [$name])
            ->where('`expires` > ?', [time()])
            ->where('`exclusive` = 1')
            ->limit(1);
        $id = null;
        if (!$query->count()) {
            // no exclusive locks exist, save this shared lock
            $id = DB::query()->insertInto(
                'locking',
                [
                    '`name`' => $name,
                    '`expires`' => time() + $ttl,
                    '`exclusive`' => false
                ]
            )->execute();
        }
        DB::commit();
        return $id ? $id : null;
    }
}
