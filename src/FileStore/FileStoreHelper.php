<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\FileStore;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;
use Digraph\Logging\LogHelper;

class FileStoreHelper extends AbstractHelper
{
    /**
     * get the image helper
     */
    public function imageHelper()
    {
        return $this->cms->helper('image');
    }

    public function allFiles($noun)
    {
        $files = [];
        foreach ($this->listPaths($noun) as $path) {
            foreach ($this->list($noun, $path) as $file) {
                $files[] = $file;
            }
        }
        return $files;
    }

    public function hook_export($export)
    {
        $out = [];
        foreach ($export['nouns'] as $noun) {
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
                $log[] = 'ERROR: Hash doesn\'t match for filestore file ' . $hash;
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
                        $log[] = 'ERROR: no valid file found matching hash ' . $file->hash();
                        continue;
                    }
                    $arr = [
                        'uniqid' => $file->uniqid(),
                        'name' => $file->name(),
                        'file' => $fname,
                        'time' => $file->time(),
                        'size' => $file->size(),
                    ];
                    $log[] = 'imported file: ' . implode(', ', [$file->uniqid(), $file->name()]);
                    $this->import($noun, $arr, $path);
                }
            }
        }
        return $log;
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
        if (!is_file($dir . '/file')) {
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
            'name' => $name,
        ];
    }

    /**
     * Set up a package to output a FileStoreFile
     */
    public function output($package, FileStoreFile $file)
    {
        //log/error if file doesn't exist
        if (!is_file($file->path())) {
            $package->error(500, 'FileStoreHelper: the specified file does not exist');
            $package['error.missing-file.name'] = $file->name();
            $package['error.missing-file.path'] = $file->path();
            return;
        }
        //otherwise output normally
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
    public function listPaths(Noun $noun): array
    {
        if (!$noun['filestore']) {
            return [];
        }
        return array_keys($noun->get('filestore'));
    }

    /**
     * List all the files at a particular path in a particular noun
     */
    function list(Noun $noun, string $path = 'default'): array
    {
        //figure out class to use
        $class = null;
        if (defined(get_class($noun) . '::FILESTORE_FILE_CLASS')) {
            $class = $noun::FILESTORE_FILE_CLASS;
        }
        if (!$class || !class_exists($class)) {
            $class = FileStoreFile::class;
        }
        //check for array in path
        if ($files = $noun["filestore.$path"]) {
            //create file objects from array
            return array_map(
                function ($e) use ($noun, $path, $class) {
                    $e['file'] = $this->dir($e['hash'], false) . '/file';
                    return new $class($e, $noun, $path, $this);
                },
                $files
            );
        }
        //return empty array if nothing is there
        return [];
    }

    /**
     * This is a CMS-aware helper
     */
    public function cms($cms = null)
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
    public function get(Noun $noun, string $s, string $path = null): array
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
    public function clear(Noun $noun, string $path = 'default')
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
                //remove from array and save
                unset($noun["filestore.$path.$uniqid"]);
                $noun->update();
            }
        }
    }

    protected function removeLineFromFile($file, $line)
    {
        if (!is_file($file)) {
            return;
        }
        //get shared lock
        $lock = fopen($file, 'w+');
        while (!flock($lock, LOCK_SH)) {
            usleep(50 + random_int(0, 100));
        }
        //get existing lines
        $contents = @file_get_contents($file);
        $lines = explode("\n", $contents);
        //filter out given line
        $lines = array_filter(
            $lines,
            function ($e) use ($line) {
                return ($e != $line);
            }
        );
        //filter and sort
        $lines = array_filter($lines);
        $lines = array_unique($lines);
        sort($lines);
        $lines = implode("\n", $lines);
        //check if new lines match original contents
        if ($lines != $contents) {
            //get exclusive lock
            $lock = fopen($file, 'w+');
            while (!flock($lock, LOCK_EX)) {
                usleep(50 + random_int(0, 100));
            }
            //write to file
            file_put_contents($file, implode("\n", $lines));
        }
        //release lock
        flock($lock, LOCK_UN);
    }

    protected function putLineInFile($file, $line)
    {
        if (!file_exists($file)) {
            touch($file);
        }
        //get shared lock
        $lock = fopen($file, 'r');
        while (!flock($lock, LOCK_SH)) {
            usleep(50 + random_int(0, 100));
        }
        //get existing lines
        $contents = @file_get_contents($file);
        $lines = explode("\n", $contents);
        //add line
        $lines[] = $line;
        //filter and sort
        $lines = array_filter($lines);
        $lines = array_unique($lines);
        sort($lines);
        $lines = implode("\n", $lines);
        //release lock
        flock($lock, LOCK_UN);
        //check if new lines match original contents
        if ($lines != $contents) {
            //write to file
            file_put_contents($file, $lines, LOCK_EX);
        }
    }

    public function addFileName($hash, $name)
    {
        $this->putLineInFile(
            $this->dir($hash) . '/names',
            $name
        );
    }

    /**
     * Copy an existing file into the filestore, without adding filename data
     *
     * @param string $filename path to existing file
     * @return boolean
     */
    public function storeFile($filename)
    {
        if (file_exists($filename)) {
            $hash = md5_file($filename);
            //identify the files we'll need
            $dir = $this->dir($hash, true);
            $storeFile = $dir . '/file';
            //only copy file to storage if it doesn't already exist
            if (!is_file($storeFile)) {
                //copy file
                if (is_uploaded_file($filename)) {
                    @unlink($storeFile);
                    if (!move_uploaded_file($filename, $storeFile)) {
                        throw new \Exception("Failed to move uploaded file $filename to $storeFile");
                    }
                } else {
                    if (!copy($filename, $storeFile)) {
                        throw new \Exception("Failed to copy file $filename to $storeFile");
                    }
                }
            }
        }
        return false;
    }

    /**
     * Copy file data into the filestore from a string, without adding any uses
     * or filenames data
     *
     * @param string $contents file content to add to filestore
     * @return boolean
     */
    public function storeFileContent($contents)
    {
        $hash = md5($contents);
        //identify the files we'll need
        $dir = $this->dir($hash, true);
        $storeFile = $dir . '/file';
        //only copy file to storage if it doesn't already exist
        if (!is_file($storeFile)) {
            if (file_put_contents($storeFile, $contents) === false) {
                throw new \Exception('Failed to write filestore file to ' . $storeFile);
            }
        }
        return true;
    }

    /**
     * Import a file to a noun at a given path. Input array $file must include
     * the following keys:
     *  * file - path to the file
     *  * name - filename to give back to users
     *  * type - mime type to give back to users
     */
    public function import(Noun $noun, array $file, string $path = 'default')
    {
        //hash file, record time
        $tries = 0;
        while (!($file['hash'] = md5_file($file['file']))) {
            $tries++;
            if ($tries > 50) {
                throw new \Exception('Failed to get hash of ' . $file['file']);
            }
            usleep(50 + random_int(0, 100));
        }
        $file['time'] = time();
        if (!$file['uniqid']) {
            $file['uniqid'] = uniqid();
        }
        //copy file into filestore directory structure
        $this->storeFile($file['file']);
        //add this filename to the filenames file
        $this->addFileName($file['hash'], $file['name']);
        //remove filename from array, because it gets regenerated when it's retrieved
        //that way moving storage locations is easier
        unset($file['file']);
        //push into noun and save
        $noun["filestore.$path." . $file['uniqid']] = $file;
        $noun->update();
        return $file['uniqid'];
    }

    /**
     * Get the directory to be used for storing a given file hash, creating it
     * by default if necessary
     */
    public function dir(string $hash, $create = true): string
    {
        $shard = substr($hash, 0, 1);
        $dir = implode(
            '/',
            [
                $this->cms->config['filestore.path'],
                $shard,
                $hash,
            ]
        );
        if (!is_dir($dir) && $create && !mkdir($dir, 0775, true)) {
            throw new \Exception("Error creating filestore directory \"$dir\"");
        }
        return $dir;
    }
}
