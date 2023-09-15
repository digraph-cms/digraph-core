<?php

namespace DigraphCMS\Cache;

class LockingDriverNull implements LockingDriver
{

    public function release(int $id): void
    {
        // does nothing
    }

    public function lock(string $name, int $ttl): ?int
    {
        return random_int(0, PHP_INT_MAX);
    }
}
