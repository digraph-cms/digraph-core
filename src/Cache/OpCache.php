<?php

namespace DigraphCMS\Cache;

use DigraphCMS\Config;
use DigraphCMS\FS;

class OpCache extends AbstractCacheDriver
{
    /** @var string */
    protected $dir;
    /** @var array<string,mixed> */
    protected $cache = [];
    /** @var array<string,mixed> */
    protected $lockHandles = [];
    /** @var int */
    protected $fuzz;
    /** @var int */
    protected $ttl;

    public function __construct()
    {
        $this->dir = Config::cachePath();
        $this->ttl = Config::get('cache.ttl');
        $this->fuzz = Config::get('cache.fuzz');
    }

    /**
     * @param string $name
     * @return non-empty-string
     */
    protected function filename(string $name): string
    {
        return $this->dir . "/" . $name . ".opcache";
    }

    /**
     * @param string $filename
     * @return mixed
     */
    protected function handle(string $filename): mixed
    {
        if (!isset($this->lockHandles[$filename]) && is_file($filename)) {
            $this->lockHandles[$filename] = fopen($filename, 'c');
        }
        return @$this->lockHandles[$filename];
    }

    protected function closeHandle(string $filename): void
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
        $content = @file_get_contents($filename, false, null, 13, 16);
        // first check for expiration of INF
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

    public function get(string $name): mixed
    {
        $this->readInternally($name);
        return @$this->cache[$name][1];
    }

    protected function readInternally(string $name, bool $force = false): void
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
                    $exp = 0;
                    $val = null;
                    /** @psalm-suppress UnresolvableInclude */
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

    public function invalidate(string $glob): static
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
        return $this;
    }

    public function set(string $name, $value, int $ttl = null): static
    {
        static::checkName($name);
        // save into internal cache
        $ttl = $ttl ?? $this->ttl;
        $exp = $ttl === -1 ? INF : time() + $ttl;
        $this->cache[$name] = [$exp, $value];
        // save into external cache if ttl is not zero
        // still save to internal memory cache even if ttl is 0
        // this means TTLs of 0 can be safely used like a fast ephemeral cache
        if ($ttl !== 0) {
            $value = static::serialize($value);
            // save into file and compile opcache
            $filename = $this->filename($name);
            FS::mkdir(dirname($filename));
            // write to file
            file_put_contents(
                $filename,
                sprintf(
                    '<?php $exp = %s; $val = %s;',
                    is_infinite($exp) ? "INF" : $exp,
                    $value
                ),
                LOCK_EX
            );
        }
        // return
        return $this;
    }

    protected static function serialize(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        } elseif ($value === true) {
            return 'true';
        } elseif ($value === false) {
            return 'false';
        } elseif (is_numeric($value)) {
            if (is_float($value) && is_infinite($value)) {
                return 'INF';
            } else {
                return "$value";
            }
        } else {
            return static::serialize_object($value);
        }
    }

    protected static function serialize_object(mixed $value): string
    {
        try {
            return sprintf(
                '\\unserialize(\'%s\')',
                str_replace("'", "\\'", \serialize($value))
            );
        } catch (\Throwable $th) {
            return sprintf(
                '@\\unserialize(\'%s\')',
                // TODO: explore replacing Opis\Closure with a wrapper for some other serializer that isn't deprecating
                str_replace("'", "\\'", @\Opis\Closure\serialize($value))
            );
        }
    }

    protected function lockWrite(string $filename): void
    {
        while (!flock($this->handle($filename), LOCK_EX)) {
            usleep(random_int(1, 100));
        }
    }

    protected function lockRead(string $filename): void
    {
        while (!flock($this->handle($filename), LOCK_SH)) {
            usleep(random_int(1, 100));
        }
    }

    protected function unlock(string $filename): void
    {
        flock($this->handle($filename), LOCK_UN);
        $this->closeHandle($filename);
    }
}
