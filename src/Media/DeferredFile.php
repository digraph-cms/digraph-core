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
        $this->identifier = md5(serialize($identifier));
    }

    public function write()
    {
        if ($this->written) {
            return;
        }
        $this->written = true;
        // if output file already exists and files.ttl config exists, don't
        // write file again if its age is less than files.ttl
        if (is_file($this->path()) && Config::get('files.ttl')) {
            if (time() < (filemtime($this->path()) + Config::get('files.ttl'))) {
                return;
            }
        }
        // create directory and call callback
        FS::mkdir(dirname($this->path()));
        call_user_func($this->content, $this);
    }

    public function content(): string
    {
        $this->write();
        return file_get_contents($this->path());
    }
}
