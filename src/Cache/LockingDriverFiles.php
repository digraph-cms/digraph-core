<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\FS;

class LockingDriverFiles implements LockingDriver
{
    protected array $handles = [];

    public function release(int $id): void
    {
        $handle = $this->handleFromId($id);
        flock($handle, LOCK_UN);
        fclose($handle);
        unset($this->handles[$id]);
    }

    public function lock(string $name, int $ttl): ?int
    {
        $id = $this->idFromName($name);
        $path = $this->pathFromId($id);
        // release if necessary and delete file if it's already expired based on given TTL
        if (file_exists($path) && filemtime($path) < time() - $ttl) {
            if (isset($this->handles[$id])) {
                $this->release($id);
            }
            unlink($path);
        }
        // as a fallback fail if file exists
        if (file_exists($path)) return null;
        // attempt to generate a handle and lock it
        // this won't work on all environments, but we're doing best-effort here I guess
        $handle = $this->handleFromName($name);
        if (flock($handle, LOCK_EX)) {
            return $id;
        } else return null;
    }

    protected function handleFromName($name)
    {
        return $this->handleFromId($this->idFromName($name));
    }

    protected function handleFromId(int $id)
    {
        if (!isset($this->handles[$id])) {
            $path = $this->pathFromId($id);
            FS::mkdir(dirname($path));
            $this->handles[$id] = fopen($path, 'r');
        }
        return $this->handles[$id];
    }

    protected function pathFromName(string $name): string
    {
        return $this->pathFromId($this->idFromName($name));
    }

    protected function idFromName(string $name): int
    {
        return crc32($name);
    }

    protected function pathFromId(int $id): string
    {
        return sprintf(
            '%s/locking/%s/%s',
            Config::get('cache.path'),
            substr("$id", 0, 1),
            $id
        );
    }
}
