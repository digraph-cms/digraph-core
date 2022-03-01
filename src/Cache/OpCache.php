<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\FS;

use function Opis\Closure\serialize;

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
        $filename = $this->filename($name);
        $content = file_get_contents($filename, false, null, 13, 16);
        // first check for expiration of 'false'
        if (substr($content, 0, 3) === 'INF') {
            return false;
        }
        // otherwise pull out numbers to form a timestamp
        $exp = substr($content, 0, 9);
        for ($i = 9; $i <= 16; $i++) {
            $char = substr($content, $i, 1);
            if ($char === ';') break;
            $exp .= $char;
        }
        return time() > intval($exp);
    }

    public function get(string $name)
    {
        $this->readInternally($name);
        return @$this->cache[$name][1];
    }

    protected function readInternally(string $name, bool $force = false)
    {
        if ($force || !isset($this->cache[$name])) {
            static::checkName($name);
            if (!$this->exists($name) || $this->expired($name)) {
                $this->cache[$name] = null;
            } else {
                $filename = $this->filename($name);
                // wait for a read lock
                $this->lockRead($filename);
                // include file
                try {
                    include $filename;
                } catch (\Throwable $th) {
                    // unlock file before throwing errors
                    $this->cache[$name] = null;
                    $this->unlock($filename);
                    throw $th;
                }
                // unlock
                $this->unlock($filename);
                // check expiration - time is randomly set back up to the fuzz amount
                if ($exp < time() - random_int(0, $this->fuzz)) {
                    // expired, expire and internally cache null result
                    $this->invalidate($name);
                    $this->cache[$name] = null;
                } else {
                    // not expired, save into internal cache
                    $this->cache[$name] = [$exp, $val];
                }
            }
        }
    }

    public function invalidate(string $glob)
    {
        static::checkGlob($glob);
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
        static::checkName($name);
        // save into internal cache
        $ttl = $ttl ?? $this->ttl;
        $exp = time() + $ttl;
        $this->cache[$name] = [$exp, $value];
        // save into external cache if ttl is > 0
        // still save to internal memory cache even if ttl is 0
        // this means TTLs of 0 can be safely used like a fast ephemeral cache
        if ($ttl > 0) {
            $value = serialize($value);
            // save into file and compile opcache
            $filename = $this->filename($name);
            FS::mkdir(dirname($filename));
            // write to file
            file_put_contents(
                $filename,
                sprintf(
                    '<?php $exp = %s; $val = \\Opis\\Closure\\unserialize(\'%s\');',
                    $exp === -1 ? "INF" : $exp,
                    str_replace('\'', '\\\'', $value)
                ),
                LOCK_EX
            );
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
