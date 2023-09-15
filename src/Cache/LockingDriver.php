<?php

namespace DigraphCMS\Cache;

interface LockingDriver
{
    public function release(int $id): void;
    public function lock(string $name, int $ttl): ?int;
}
