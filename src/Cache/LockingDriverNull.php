<?php

namespace DigraphCMS\Cache;

class LockingDriverNull implements LockingDriver
{

    public function release(string $name): void
    {
        // does nothing
    }

    public function lock(string $name, int $ttl): bool
    {
        return true;
    }
}
