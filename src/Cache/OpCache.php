<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\FS;

class OpCache extends AbstractCacheDriver
{
    protected $dir;
    protected $cache = [];

    public function __construct()
    {
        $this->dir = Config::get('cache.path');
        $this->ttl = Config::get('cache.ttl');
    }

    protected function filename(string $name): string
    {
        return $this->dir . "/$name.opcache";
    }

    public function exists(string $name): bool
    {
        return is_file($this->filename($name));
    }

    public function expired(string $name): bool
    {
        $this->readInternally($name);
        return time() > @$this->cache[$name][0];
    }

    public function get(string $name, callable $callback = null, int $ttl = null)
    {
        $this->readInternally($name);
        return @$this->cache[$name][1];
    }

    protected function readInternally(string $name, bool $force = false)
    {
        if ($force || !isset($this->cache[$name])) {
            Cache::checkName($name);
            if (!$this->exists($name)) {
                $this->cache[$name] = null;
            } else {
                include $this->filename($name);
                $this->cache[$name] = [$exp, $val];
            }
        }
    }

    public function set(string $name, $value, int $ttl = null)
    {
        Cache::checkName($name);
        $value = var_export($value, true);
        $exp = time() + ($ttl ?? $this->ttl);
        // save into file and compile opcache
        $filename = $this->filename($name);
        FS::mkdir(dirname($filename));
        file_put_contents(
            $filename,
            "<?php \$exp = $exp; \$val = $value;",
            LOCK_EX
        );
        opcache_compile_file($filename);
        // save into internal cache
        $this->cache[$name] = [$exp, $value];
    }
}
