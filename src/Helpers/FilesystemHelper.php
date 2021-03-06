<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;
use Digraph\Helpers\AbstractHelper;

class FilesystemHelper extends AbstractHelper
{
    protected $umask_file = 0117;
    protected $umask_dir = 0002;
    protected $umask_prev;

    public function __construct(CMS $cms)
    {
        parent::__construct($cms);
        $this->umask_prev = \umask();
    }

    public function tempFile(string $ext = 'tmp'): string
    {
        do {
            $file = \sys_get_temp_dir() . '/' . uniqid() . '.' . $ext;
        } while (\file_exists($file));
        return $file;
    }

    public function put($string, $dest, $overwrite = false)
    {
        if (\file_exists($dest) && !$overwrite) {
            return;
        }
        $temp = $this->tempFile();
        \umask($this->umask_file);
        \file_put_contents($temp, $string);
        \umask($this->umask_prev);
        $this->move($temp, $dest, $overwrite);
    }

    public function move($source, $dest, $overwrite = false, $allow_uploads = false)
    {
        if (\file_exists($dest) && !$overwrite) {
            return;
        }
        if ($allow_uploads && \is_uploaded_file($source)) {
            $this->mkdir_for($dest);
            \umask($this->umask_file);
            \move_uploaded_file($source, $dest);
            \umask($this->umask_prev);
        } else {
            $this->mkdir_for($dest);
            \umask($this->umask_file);
            \rename($source, $dest);
            \umask($this->umask_prev);
        }
    }

    public function copy($source, $dest, $overwrite = false, $link = false)
    {
        if (\file_exists($dest) && !$overwrite) {
            return;
        }
        $this->mkdir_for($dest);
        \umask($this->umask_file);
        if ($link && $this->cms->config['filesystem.symlinks']) {
            // symlink if requested and enabled
            if (file_exists($dest)) {
                \unlink($dest);
            }
            \symlink($source, $dest);
        } else {
            // otherwise copy
            \copy($source, $dest);
        }
        \umask($this->umask_prev);
    }

    public function mkdir($path)
    {
        if (!$path) {
            return;
        }
        $parent = dirname($path);
        if (is_file($path)) {
            throw new \Exception("Couldn't mkdir <code>" . \htmlentities($path) . "</code> because a file exists with the same name.");
        }
        if (!is_dir($path)) {
            if (!is_dir($parent)) {
                $this->mkdir($parent);
            }
            if (!\is_writeable($parent)) {
                throw new \Exception("Couldn't mkdir <code>" . \htmlentities($path) . "</code> because parent isn't writeable.");
            }
            \umask($this->umask_dir);
            mkdir($path);
            \umask($this->umask_prev);
        }
        return is_dir($path);
    }

    public function mkdir_for($dest)
    {
        return $this->mkdir(dirname($dest));
    }
}
