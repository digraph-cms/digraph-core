<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\DSO\Noun;

class FileStore extends AbstractHelper
{
    public function list(Noun &$noun, string $path = 'default') : array
    {
        //check for array in
        if ($files = $noun["filestore.$path"]) {
            //create FileStoreFile objects from array
            return array_map(
                function ($e) use ($noun,$path) {
                    $e['file'] = $this->dir($e['hash']).'/file';
                    return new FileStoreFile($e, $noun, $path, $this);
                },
                $files
            );
        }
        //return empty array if nothing is there
        return [];
    }

    public function get(Noun &$noun, string $path = 'default', string $s) : array
    {
        return array_values(array_filter(
            $this->list($noun, $path),
            function ($file) use ($s) {
                return $file->name() == $s || $file->uniqid() == $s;
            }
        ));
    }

    public function clear(Noun &$noun, string $path = 'default')
    {
        foreach ($this->list($noun, $path) as $file) {
            $this->delete($noun, $path, $file->uniqid());
        }
    }

    public function import(Noun &$noun, array $file, string $path = 'default', $copy = true)
    {
        //hash file, record time
        $file['hash'] = md5_file($file['file']);
        $file['time'] = time();
        $file['uniqid'] = uniqid().'.'.$noun['dso.id'];
        //identify the files we'll need
        $dir = $this->dir($file['hash']);
        $storeFile = $dir.'/file';
        $usesFile = $dir.'/uses';
        $lockFile = $dir.'/lock';
        //do nothing if file with this hash already exists
        if (!is_file($storeFile)) {
            //touch uses file
            touch($usesFile);
            touch($lockFile);
            //move/dopy file
            $oldName = $file['file'];
            if (is_uploaded_file($oldName)) {
                if (!move_uploaded_file($oldName, $storeFile)) {
                    throw new \Exception("Failed to move uploaded file $oldName to $storeFile");
                }
            } elseif ($copy) {
                if (!copy($oldName, $storeFile)) {
                    throw new \Exception("Failed to copy file $oldName to $storeFile");
                }
            } else {
                if (!rename($oldName, $storeFile)) {
                    throw new \Exception("Failed to rename file $oldName to $storeFile");
                }
            }
        }
        //get lock of lock file
        $lockHandle = fopen($lockFile, 'r');
        while (!flock($lockHandle, LOCK_EX)) {
            usleep(50+random_int(0, 100));
        }
        //add this noun's id to the uses file
        $uses = file_get_contents($usesFile);
        if (strpos($uses, $file['uniqid']."\n") === false) {
            $uses .= $file['uniqid']."\n";
            file_put_contents($usesFile, $uses);
        }
        //release lock on lock file
        flock($lockHandle, LOCK_UN);
        //remove filename from array, because it gets regenerated when it's retrieved
        //that way moving storage locations is easier
        unset($file['file']);
        //save into file
        if (!$noun["filestore.$path"]) {
            $noun["filestore.$path"] = [];
        }
        //push into noun and save
        $noun->push("filestore.$path", $file);
        $noun->update();
    }

    public function dir(string $hash) : string
    {
        $shard = substr($hash, 0, 2);
        $dir = implode('/', [
                $this->cms->config['paths.storage'],
                'filestore',
                $shard,
                $hash
            ]);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \Exception("Error creating filestore directory \"$dir\"");
        }
        return $dir;
    }

    protected function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir."/".$object)) {
                        $this->rrmdir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
