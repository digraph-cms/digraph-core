<?php

namespace DigraphCMS\Cache;

interface LockingDriver
{
    public function release(string $name): void;
    public function lock(string $name, int $ttl): bool;
}
