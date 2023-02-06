<?php

namespace DigraphCMS\Cache;

use DigraphCMS\DB\DB;

class LockingDriverDB implements LockingDriver
{

    public function release(int $id): void
    {
        DB::query()
            ->delete('locking', $id)
            ->execute();
    }

    public function getExclusiveLock(string $name, int $ttl): ?int
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

    public function getSharedLock(string $name, int $ttl): ?int
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
