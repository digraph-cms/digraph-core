<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\FileStore;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;

class FileStoreHelper extends AbstractHelper
{
    /**
     * get the image helper
     */
    public function imageHelper()
    {
        return $this->cms->helper('image');
    }

    public function hook_export(&$export)
    {
        $out = [];
        foreach ($export['noun_ids'] as $noun) {
            $noun = $this->cms->read($noun);
            foreach ($this->listPaths($noun) as $path) {
                foreach ($this->list($noun, $path) as $file) {
                    $out[$file->hash()] = base64_encode(file_get_contents($file->path()));
                }
            }
        }
        return $out;
    }

    public function hook_import($data, $nouns)
    {
        $log = [];
        //set up temp files to hold imported files
        $tmpfiles = [];
        foreach ($data['helper']['filestore'] as $hash => $data) {
            $data = base64_decode($data);
            if ($hash == md5($data)) {
                $fn = $tmpfiles[$hash] = tempnam(sys_get_temp_dir(), 'import');
                file_put_contents($fn, $data);
            } else {
                $log[] = 'ERROR: Hash doesn\'t match for filestore file '.$hash;
            }
        }
        //re-add all files from imported nouns
        foreach ($nouns as $noun) {
            foreach ($this->listPaths($noun) as $path) {
                foreach ($this->list($noun, $path) as $file) {
                    $fname = @$tmpfiles[$file->hash()];
                    if (!$fname) {
                        if ($fname = @$this->getByHash($file->hash())['file']) {
                            $log[] = 'WARNING: exported file is invalid, but matching hash was found in filestore';
                        }
                    }
                    if (!$fname) {
                        $log[] = 'ERROR: no valid file found matching hash '.$file->hash();
                        continue;
                    }
                    $arr = [
                        'uniqid' => $file->uniqid(),
                        'name' => $file->name(),
                        'file' => $fname,
                        'time' => $file->time()
                    ];
                    $log[] = 'imported file: '.implode(', ', [$file->uniqid(),$file->name()]);
                    $this->import($noun, $arr, $path);
                }
            }
        }
        return $log;
    }

    protected function importFile($fdata, $id, $farr)
    {
    }

    /**
     * Retrieve an array of information about a file by its hash
     */
    public function getByHash(string $hash)
    {
        try {
            $dir = $this->dir($hash, false);
        } catch (\Exception $e) {
            return null;
        }
        if (!is_file($dir.'/file')) {
            return null;
        }
        if ($name = trim(@file_get_contents("$dir/names"))) {
            $name = explode("\n", $name);
            $name = array_shift($name);
        } else {
            $name = $hash;
        }
        return [
            'dir' => $dir,
            'file' => "$dir/file",
            'name' => $name
        ];
    }

    /**
     * Call cleanup() and then attempt to actually delete the cleanup-able files
     */
    public function cleanupRun() : array
    {
        //clean up files
        $files = $this->cleanup();
        $files = array_map(
            function ($e) {
                $dir = $e['dir'];
                @unlink("$dir/file");
                @unlink("$dir/names");
                @unlink("$dir/uses");
                $e['deleted'] = @rmdir($dir);
                return $e;
            },
            $files
        );
        //clean up empty directories
        $this->cleanupEmptyDirs();
        //return file result
        return $files;
    }

    protected function cleanupEmptyDirs($dir = null)
    {
        if ($dir === null) {
            $dir = $this->cms->config['filestore.path'];
        }
        //recurse
        foreach (glob("$dir/*") as $s) {
            if (is_dir($s)) {
                $this->cleanupEmptyDirs($s);
            }
        }
        //remove if empty
        $children = glob("$dir/*");
        if (!$children) {
            rmdir($dir);
        }
    }

    /**
     * returns an array of all the files that are currently not referenced
     */
    public function cleanup() : array
    {
        $dirs = glob($this->cms->config['filestore.path'].'/*/*');
        $out = [];
        foreach ($dirs as $dir) {
            if (@filesize("$dir/uses") < 1) {
                $names = [];
                if ($names = trim(@file_get_contents("$dir/names"))) {
                    $names = explode("\n", $names);
                }
                $out[] = [
                    'dir' => $dir,
                    'size' => @filesize("$dir/file"),
                    'hash' => @md5_file("$dir/file"),
                    'mtime' => @filemtime("$dir/file"),
                    'names' => $names
                ];
            }
        }
        return $out;
    }

    /**
     * Set up a package to output a FileStoreFile
     */
    public function output(&$package, FileStoreFile $file)
    {
        $package->makeMediaFile($file->nameWithHash());
        $package['response.outputmode'] = 'readfile';
        $package['response.readfile'] = $file->path();
        unset($package['response.content']);
        $package['response.cacheable'] = true;
        $package['response.ttl'] = $this->cms->config['media.package.response.ttl'];
        $package['response.last-modified'] = $file->time();
    }

    /**
     * List all the paths (namespaces) in use by a noun
     */
    public function listPaths(Noun &$noun) : array
    {
        if (!$noun['filestore']) {
            return [];
        }
        return array_keys($noun->get('filestore'));
    }

    /**
     * List all the files at a particular path in a particular noun
     */
    public function list(Noun &$noun, string $path = 'default') : array
    {
        //check for array in path
        if ($files = $noun["filestore.$path"]) {
            //create FileStoreFile objects from array
            return array_map(
                function ($e) use ($noun,$path) {
                    $e['file'] = $this->dir($e['hash'], false).'/file';
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

    /**
     * get a file from a noun -- searches by both name and uniqid, and might
     * return more than one result
     */
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

    /**
     * clear all files at a particular path in a noun
     */
    public function clear(Noun &$noun, string $path = 'default')
    {
        foreach ($this->list($noun, $path) as $file) {
            $this->delete($noun, $file->uniqid());
        }
    }

    /**
     * Delete a file with a given uniqid from a noun
     */
    public function delete($noun, $uniqid)
    {
        //loop through paths
        foreach ($noun['filestore'] as $path => $files) {
            if (isset($files[$uniqid])) {
                $file = $files[$uniqid];
                //identify the files we'll need
                $dir = $this->dir($file['hash'], false);
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
                $useID = $noun['dso.id'].'.'.$file['uniqid'];
                if (strpos($uses, $useID."\n") !== false) {
                    $uses = str_replace($useID."\n", '', $uses);
                    file_put_contents($usesFile, $uses);
                }
                //release lock on lock file
                flock($lockHandle, LOCK_UN);
                //remove from array and save
                unset($noun["filestore.$path.$uniqid"]);
                $noun->update();
            }
        }
    }

    /**
     * Import a file to a noun at a given path. Input array $file must include
     * the following keys:
     *  * file - path to the file
     *  * name - filename to give back to users
     *  * type - mime type to give back to users
     */
    public function import(Noun &$noun, array $file, string $path = 'default', $copy = true)
    {
        //hash file, record time
        if (!file_exists($file['file'])) {
            throw new \Exception("File doesn't exist: $file[file]");
        }
        $file['hash'] = md5_file($file['file']);
        $file['time'] = time();
        if (!$file['uniqid']) {
            $file['uniqid'] = uniqid();
        }
        //identify the files we'll need
        $dir = $this->dir($file['hash']);
        $storeFile = $dir.'/file';
        $usesFile = $dir.'/uses';
        $namesFile = $dir.'/names';
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
        //add this filename to the filenames file
        $uses = @file_get_contents($namesFile);
        if (strpos($uses, $file['name']."\n") === false) {
            $uses .= $file['name']."\n";
            file_put_contents($namesFile, $uses);
        }
        //add this uniqid to the uses file
        $uses = @file_get_contents($usesFile);
        $useID = $noun['dso.id'].'.'.$file['uniqid'];
        if (strpos($uses, $useID."\n") === false) {
            $uses .= $useID."\n";
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
        $noun["filestore.$path.".$file['uniqid']] = $file;
        $noun->update();
    }

    /**
     * Get the directory to be used for storing a given file hash, creating it
     * by default if necessary
     */
    public function dir(string $hash, $create=true) : string
    {
        $shard = substr($hash, 0, 1);
        $dir = implode(
            '/',
            [
                $this->cms->config['filestore.path'],
                $shard,
                $hash
            ]
        );
        if (!is_dir($dir) && $create && !mkdir($dir, 0775, true)) {
            throw new \Exception("Error creating filestore directory \"$dir\"");
        }
        return $dir;
    }
}
