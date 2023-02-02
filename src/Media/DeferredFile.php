<?php

namespace DigraphCMS\Media;

use DigraphCMS\Config;
use DigraphCMS\FS;

class DeferredFile extends File
{
    protected $stringContent;

    public function __construct(string $filename, callable $content, $identifier)
    {
        // take in filename/extension
        $this->filename = $filename;
        $this->extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        // double check extension is valid
        if (strlen($this->extension) == 0 || preg_match('/[^a-z0-9]/', $this->extension)) {
            throw new \Exception("Filename $filename has an invalid extension");
        }
        // take in content/identifier
        $this->content = $content;
        $this->identifier = md5(@\Opis\Closure\serialize($identifier));
    }

    public function write()
    {
        if ($this->written) {
            return;
        }
        $this->written = true;
        // if output file already exists and ttl config exists, don't
        // write file again if its age is less than ttl
        if (is_file($this->path()) && $this->ttl()) {
            // -1 means cache files forever
            if ($this->ttl() == -1) {
                return;
            }
            // otherwise do the math
            if (time() < (filemtime($this->path()) + $this->ttl())) {
                return;
            }
        }
        // create directory and call callback
        FS::mkdir(dirname($this->path()));
        call_user_func($this->content, $this);
        // reset URL
        $this->url = null;
    }

    /**
     * Cache TTL pulled once and stored statically both for performance,
     * and to retrieve it via a function so that child classes can 
     * have their own TTLs.
     *
     * @return integer
     */
    public function ttl(): int
    {
        static $ttl;
        return $ttl ?? $ttl = (Config::get('files.ttl') ?? 3600);
    }

    public function content(): string
    {
        $this->write();
        return file_get_contents($this->path());
    }
}
