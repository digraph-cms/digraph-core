<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\FS;

class LockingDriverFiles implements LockingDriver
{
    protected array $handles = [];

    public function release(string $name): void
    {
        if ($handle = @$this->handles[$name]) {
            flock($handle, LOCK_UN);
            fclose($handle);
            unset($this->handles[$name]);
        }
        unlink($this->path($name));
    }

    public function lock(string $name, int $ttl): bool
    {
        $path = $this->path($name);
        // release if necessary and delete file if it's already expired based on given TTL
        if (file_exists($path) && filemtime($path) < time() - $ttl) {
            if (isset($this->handles[$name])) {
                $this->release($name);
            }
            unlink($path);
        }
        // as a fallback fail if file exists
        if (file_exists($path)) return false;
        // attempt to generate a handle and lock it for extra assurance
        // this won't work on all environments, but we're doing best-effort here I guess
        $handle = $this->handle($name);
        if (flock($handle, LOCK_EX)) return true;
        else return false;
    }

    protected function handle(string $name)
    {
        if (!isset($this->handles[$name])) {
            $path = $this->path($name);
            if (!file_exists($path)) FS::touch($path);
            $this->handles[$name] = fopen($path, 'r');
        }
        return $this->handles[$name];
    }

    protected function path(string $name): string
    {
        return sprintf(
            '%s/locking/%s',
            Config::get('cache.path'),
            $name
        );
    }
}
