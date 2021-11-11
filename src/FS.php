<?php

namespace DigraphCMS;

class FS
{
    public static $umask_file, $umask_dir;

    public static function _init()
    {
        self::$umask_file = Config::get('fs.umask_file');
        self::$umask_dir = Config::get('fs.umask_dir');
    }

    public static function delete(string $file, string $deleteEmptyDirsUntil = null)
    {
        if ($file = realpath($file)) {
            unlink($file);
        }
        if ($deleteEmptyDirsUntil) {
            static::deleteEmptyDirsUntil(dirname($file), $deleteEmptyDirsUntil);
        }
    }

    public static function deleteEmptyDirsUntil(string $dir, string $until)
    {
        if (($dir = realpath($dir)) && ($until = realpath($until))) {
            if ($dir != $until && strpos($dir, $until) === 0) {
                @rmdir($dir);
                static::deleteEmptyDirsUntil(dirname($dir), $until);
            }
        }
    }

    public static function mirror(string $src, string $dest, $link = false)
    {
        if (!is_dir($src)) {
            throw new \Exception("Couldn't mirror $src because it's not a directory.");
        }
        self::mkdir($dest);
        if ($handle = opendir($src)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                if (is_dir($src . '/' . $entry)) {
                    self::mirror(
                        $src . '/' . $entry,
                        $dest . '/' . $entry,
                        $link
                    );
                } else {
                    self::copy(
                        $src . '/' . $entry,
                        $dest . '/' . $entry,
                        $link
                    );
                }
            }
        }
    }

    public static function copy(string $src, string $dest, $link = false, $allow_uploads = false)
    {
        $umask = umask(self::$umask_file);
        if ($allow_uploads && is_uploaded_file($src)) {
            move_uploaded_file($src, $dest);
        } elseif ($link && Config::get('fs.links')) {
            symlink($src, $dest);
        } else {
            copy($src, $dest);
        }
        umask($umask);
    }

    /**
     * Make a directory, including parent directories.
     *
     * @param string $path
     * @return void
     */
    public static function mkdir(string $path)
    {
        if (!is_dir($path)) {
            $parent = dirname($path);
            if (!is_dir($parent)) {
                self::mkdir($parent);
            }
            if (!\is_writeable($parent)) {
                throw new \Exception("Couldn't mkdir $path because parent isn't writeable.");
            }
            $umask = umask(self::$umask_dir);
            mkdir($path);
            umask($umask);
        }
        return is_dir($path);
    }
}

FS::_init();
