<?php

namespace DigraphCMS\Cache;

interface LockingDriver
{
    public function release(int $id);
    public function getExclusiveLock(string $name, int $ttl): ?int;
    public function getSharedLock(string $name, int $ttl): ?int;
}
