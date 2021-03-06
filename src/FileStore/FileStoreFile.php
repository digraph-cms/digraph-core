<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */
namespace Digraph\FileStore;

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

    //keys that are not allowed to be changed with set()
    const PROTECTED_KEYS = [
        'size',
        'type',
        'uniqid',
        'hash',
        'time',
    ];

    public function __construct(array $e, Noun $noun, string $path, FileStoreHelper $fs)
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
        $this->asset = $fs->cms()->helper('media')->create(
            $this->name,
            function ($dest) {
                $this->fs->cms()->helper('filesystem')->copy(
                    $this->file,
                    $dest,
                    false, //don't overwrite
                    true//use link if possible
                );
            },
            $this->hash
        );
    }

    public function noun()
    {
        return $this->noun;
    }

    public function set($key, $value)
    {
        //don't allow changing values that might break things
        if (in_array($key, static::PROTECTED_KEYS)) {
            return;
        }
        //find this file in the given noun
        foreach ($this->noun['filestore'] as $section => $files) {
            if (isset($files[$this->uniqid()])) {
                $this->noun["filestore.$section." . $this->uniqid() . ".$key"] = $value;
            }
        }
    }

    public function exif()
    {
        return exif_read_data($this->path());
    }

    public function metaCard($meta = null, $noLink = false)
    {
        if ($meta === null) {
            $meta = ['time', 'size'];
            if (method_exists($this->noun, 'fileMetaCardMeta')) {
                $meta = $this->noun->fileMetaCardMeta();
            }
        }
        $s = $this->fs->cms()->helper('strings');
        $out = '<div class="digraph-card filestore-card">';
        if (!$noLink) {
            $out .= '<a target="_top" href="' . $this->url() . '">';
        }
        $out .= $this->thumbnail(true);
        if (!$noLink) {
            $out .= '</a>';
        }
        if ($meta) {
            $out .= PHP_EOL . '<dl>';
            foreach ($meta as $i) {
                switch ($i) {
                    case 'time':
                        $out .= $this->metaCard_attr($s->string('filestore.meta.time'), $s->datetimeHTML($this->time()));
                        break;
                    case 'size':
                        $out .= $this->metaCard_attr($s->string('filestore.meta.size'), $s->filesizeHTML($this->size()));
                        break;
                    case 'hash':
                        $out .= $this->metaCard_attr($s->string('filestore.meta.hash'), $this->hash());
                        break;
                }
            }
            $out .= '</dl>';
        }
        $out .= '</div>';
        return $out;
    }

    protected function metaCard_attr($name, $value)
    {
        return PHP_EOL . '<dt>' . $name . '</dt>' . PHP_EOL . '<dd>' . $value . '</dd>';
    }

    public function thumbnail($name = false)
    {
        $out = PHP_EOL . '<span class="filestore-thumbnail">';
        $out .= '<span class="filestore-icon">';
        $out .= $this->icon();
        $out .= '</span>';
        if ($name) {
            $out .= ' <span class="filestore-filename">' . $this->name() . '</span>';
        }
        $out .= '</span>';
        return $out;
    }

    public function extension()
    {
        return strtolower(preg_replace('/.+\./', '', $this->name()));
    }

    public function isImage()
    {
        return $this->fs->imageHelper()->supports($this->extension());
    }

    public function icon()
    {
        $conf = $this->fs->cms()->config['filestore.icons'];
        $icon = $conf['default'];
        //simple search by left side of mime
        $mime_left = preg_replace('/\/.+/', '', $this->type());
        if (isset($conf['mime_left'][$mime_left])) {
            $icon = $conf['mime_left'][$mime_left];
        }
        //search by extension
        $extension = preg_replace('/.+\./', '', $this->name());
        if (isset($conf['extension'][$extension])) {
            $icon = $conf['extension'][$extension];
        }
        //see if we can make a thumbnail
        if ($this->isImage()) {
            $icon = '<img src="' . $this->imageUrl('filestore-thumbnail') . '">';
        }
        //return whatever we found
        return $icon;
    }

    public function imageUrl($preset)
    {
        $asset = $this->fs->cms()
            ->helper('image')
            ->create($this->asset['path'], $preset);
        return $asset['url'];
    }

    public function url()
    {
        return $this->asset['url'];
    }

    public function path()
    {
        return $this->file;
    }

    public function name($set = null)
    {
        if ($set !== null) {
            $this->name = $set;
            $this->set('name', $set);
        }
        return $this->name;
    }

    public function nameWithHash()
    {
        $name = $this->name();
        if (strpos($name, '.')) {
            $name = preg_replace('/((\.[\.a-z0-9]+)+)$/', '.' . $this->miniHash() . '$1', $name);
        } else {
            $name .= '_' . $this->miniHash();
        }
        return $name;
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

    public function miniHash()
    {
        return substr($this->hash(), 0, 4);
    }

    public function time()
    {
        return $this->time;
    }
}
