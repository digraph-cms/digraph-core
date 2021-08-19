<?php

namespace DigraphCMS\Media;

use DigraphCMS\Config;
use DigraphCMS\FS;

class File
{
    protected $filename, $extension, $content, $identifier;

    public function __construct(string $filename, string $content, $identifier = null)
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
        $this->identifier = $identifier ? md5(serialize($identifier)) : md5($content);
    }

    public function __toString()
    {
        return $this->embed();
    }

    public function embed(): string
    {
        switch ($this->extension()) {
            case 'css':
                return '<link rel="stylesheet" href="' . $this->url() . '" />';
            case 'js':
                return '<script src="' . $this->url() . '"></script>';
        }
        throw new \Exception("Don't know how to embed a " . $this->extension() . " file");
    }

    public function filename(): string
    {
        return $this->filename;
    }

    public function extension(): string
    {
        return $this->extension;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function path(): string
    {
        return Media::filePath($this);
    }

    public function url(): string
    {
        return Media::fileUrl($this);
    }

    public function write()
    {
        // if output file already exists and files.ttl config exists, don't
        // write file again if its age is less than files.ttl
        if (is_file($this->path()) && Config::get('files.ttl')) {
            if (time() < (filemtime($this->path()) + Config::get('files.ttl'))) {
                return;
            }
        }
        // create directory and put content in file
        FS::mkdir(dirname($this->path()));
        file_put_contents($this->path(), $this->content());
    }

    public function content(): string
    {
        return $this->content;
    }
}