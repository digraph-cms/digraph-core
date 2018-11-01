<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\DSO\Noun;

class FileStoreFile
{
    protected $fs;
    protected $file;
    protected $name;
    protected $size;
    protected $type;
    protected $uniqid;
    protected $hash;
    protected $time;
    protected $path;

    public function __construct(array $e, Noun &$noun, string $path, FileStore &$fs)
    {
        $this->fs = $fs;
        $this->noun = $noun;
        $this->path = $path;
        $this->file = $e['file'];
        $this->name = $e['name'];
        $this->size = $e['size'];
        $this->type = $e['type'];
        $this->uniqid = $e['uniqid'];
        $this->hash = $e['hash'];
        $this->time = $e['time'];
    }

    public function path()
    {
        return $this->file;
    }

    public function name($set = null)
    {
        if ($set !== null) {
            $this->name = $set;
            $this->setInNoun('name', $set);
        }
        return $this->name;
    }

    protected function setInNoun($name, $value)
    {
    }

    public function update()
    {
        return $this->noun->update();
    }

    public function size()
    {
        return $this->size;
    }

    public function type()
    {
        return $this->type;
    }

    public function uniqid()
    {
        return $this->uniqid;
    }

    public function hash()
    {
        return $this->hash;
    }

    public function time()
    {
        return $this->time;
    }
}
