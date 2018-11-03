<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\FileStore;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;

class FileStoreHelper extends AbstractHelper
{
    public function output(&$package, FileStoreFile $file)
    {
        $package->makeMediaFile($file->nameWithHash());
        $package['response.readfile'] = $file->path();
        unset($package['response.content']);
        $package['response.cacheable'] = true;
        $package['response.ttl'] = $this->cms->config['media.package.response.ttl'];
        $package['response.last-modified'] = $file->time();
    }

    public function list(Noun &$noun, string $path = 'default') : array
    {
        //check for array in path
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

    public function cms(&$cms = null)
    {
        if ($cms !== null) {
            $this->cms = $cms;
        }
        return $this->cms;
    }

    public function get(Noun &$noun, string $s, string $path = null) : array
    {
        //loop through all paths
        if ($path === null) {
            $out = [];
            if ($noun['filestore']) {
                foreach ($noun['filestore'] as $path => $value) {
                    $out = $out + $this->get($noun, $s, $path);
                }
            }
            return $out;
        }
        //get by path
        return array_values(
            array_filter(
                $this->list($noun, $path),
                function ($file) use ($s) {
                    return $file->name() == $s || $file->uniqid() == $s;
                }
            )
        );
    }

    public function clear(Noun &$noun, string $path = 'default')
    {
        foreach ($this->list($noun, $path) as $file) {
            $this->delete($noun, $file->uniqid());
        }
    }

    public function delete($noun, $uniqid)
    {
        $key = md5($uniqid);
        //loop through paths
        foreach ($noun['filestore'] as $path => $files) {
            if (isset($files[$key])) {
                $file = $files[$key];
                //identify the files we'll need
                $dir = $this->dir($file['hash']);
                $storeFile = $dir.'/file';
                $usesFile = $dir.'/uses';
                //short-circuit and give up if storefile isn't there
                if (!is_file($storeFile)) {
                    return;
                }
                //get lock of lock file
                $lockHandle = fopen($storeFile, 'r');
                while (!flock($lockHandle, LOCK_EX)) {
                    usleep(50+random_int(0, 100));
                }
                //remove this uniqid from the uses file
                $uses = file_get_contents($usesFile);
                if (strpos($uses, $file['uniqid']."\n") !== false) {
                    $uses = str_replace($file['uniqid']."\n", '', $uses);
                    file_put_contents($usesFile, $uses);
                }
                //release lock on lock file
                flock($lockHandle, LOCK_UN);
                //remove from array and save
                unset($noun["filestore.$path.$key"]);
                $noun->update();
            }
        }
    }

    public function import(Noun &$noun, array $file, string $path = 'default', $copy = true)
    {
        //hash file, record time
        $file['hash'] = md5_file($file['file']);
        $file['time'] = time();
        $file['uniqid'] = uniqid();
        //identify the files we'll need
        $dir = $this->dir($file['hash']);
        $storeFile = $dir.'/file';
        $usesFile = $dir.'/uses';
        //do nothing if file with this hash already exists
        if (!is_file($storeFile)) {
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
        $lockHandle = fopen($storeFile, 'r');
        while (!flock($lockHandle, LOCK_EX)) {
            usleep(50+random_int(0, 100));
        }
        //add this uniqid to the uses file
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
        $noun["filestore.$path.".md5($file['uniqid'])] = $file;
        $noun->update();
    }

    public function dir(string $hash) : string
    {
        $shard = substr($hash, 0, 2);
        $dir = implode(
            '/', [
                $this->cms->config['paths.storage'],
                'filestore',
                $shard,
                $hash
            ]
        );
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
