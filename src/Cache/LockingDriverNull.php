<?php

namespace DigraphCMS\Cache;

class LockingDriverNull implements LockingDriver
{

    public function release(int $id)
    {
        // does nothing
    }

    public function getExclusiveLock(string $name, int $ttl): ?int
    {
        return 1;
    }

    public function getSharedLock(string $name, int $ttl): ?int
    {
        return 1;
    }
}
