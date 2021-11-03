<?php

namespace DigraphCMS\Cache;

use Brick\VarExporter\VarExporter;
use DigraphCMS\Config;
use DigraphCMS\FS;

class OpCache extends AbstractCacheDriver
{
    protected $dir;
    protected $cache = [];
    protected $lockHandles = [];
    protected $fuzz;

    public function __construct()
    {
        $this->dir = Config::get('cache.path');
        $this->ttl = Config::get('cache.ttl');
        $this->fuzz = Config::get('cache.fuzz');
    }

    protected function filename(string $name): string
    {
        return $this->dir . "/" . $name . ".opcache";
    }

    protected function handle(string $filename)
    {
        if (!isset($this->lockHandles[$filename]) && is_file($filename)) {
            $this->lockHandles[$filename] = fopen($filename, 'c');
        }
        return @$this->lockHandles[$filename];
    }

    protected function closeHandle(string $filename)
    {
        if (isset($this->lockHandles[$filename])) {
            fclose($this->lockHandles[$filename]);
            unset($this->lockHandles[$filename]);
        }
    }

    public function exists(string $name): bool
    {
        return @$this->cache[$name][0] ?? is_file($this->filename($name));
    }

    public function expired(string $name): bool
    {
        $this->readInternally($name);
        return time() > @$this->cache[$name][0];
    }

    public function get(string $name)
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
                $filename = $this->filename($name);
                // wait for a read lock
                $this->lockRead($filename);
                // include file
                include $filename;
                // unlock
                $this->unlock($filename);
                // check expiration
                if (time() > $exp) {
                    // expired, expire and internally cache null result
                    $this->invalidate($name);
                    $this->cache[$name] = null;
                } else {
                    // not expired, save into internal cache
                    $this->cache[$name] = [$exp + random_int(-$this->fuzz, $this->fuzz), $val];
                }
            }
        }
    }

    public function invalidate(string $glob)
    {
        Cache::checkGlob($glob);
        $glob = $this->filename($glob);
        foreach (glob($glob) as $filename) {
            $name = substr($filename, 0, strlen($this->dir) + 1);
            $name = substr($name, 0, strlen($name) - 8);
            $this->cache[$name] = null;
            $this->unlock($filename);
            unlink($filename);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($filename);
            }
        }
    }

    public function set(string $name, $value, int $ttl = null)
    {
        Cache::checkName($name);
        // save into internal cache
        $ttl = $ttl ?? $this->ttl;
        $exp = time() + $ttl;
        $this->cache[$name] = [$exp, $value];
        // save into external cache if ttl is > 0
        // this means TTLs of 0 can be safely used like a fast ephemeral cache
        if ($ttl > 0) {
            // Right now this is wrapped in a try/catch and the options to VarExporter
            // are hardcoded, because that way it fails silently for closures that
            // include use() statements on older versions of PHP.
            // This is hacky, but it's the most viable way to do this as long as
            // we're trying to support PHP 7.1 (and maybe 7.2).
            try {
                $value = VarExporter::export($value, 1 << 8);
                // save into file and compile opcache
                $filename = $this->filename($name);
                FS::mkdir(dirname($filename));
                // write
                file_put_contents(
                    $filename,
                    "<?php \$exp = $exp; \$val = $value;",
                    LOCK_EX
                );
            } catch (\Throwable $th) {
                //throw $th;
            }
        }
    }

    protected function lockWrite(string $filename)
    {
        while (!flock($this->handle($filename), LOCK_EX)) {
            usleep(random_int(1, 100));
        }
    }

    protected function lockRead(string $filename)
    {
        while (!flock($this->handle($filename), LOCK_SH)) {
            usleep(random_int(1, 100));
        }
    }

    protected function unlock(string $filename)
    {
        flock($this->handle($filename), LOCK_UN);
        $this->closeHandle($filename);
    }
}
