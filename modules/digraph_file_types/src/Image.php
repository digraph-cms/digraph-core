<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_file_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class Image extends File
{
    const ROUTING_NOUNS = ['image','file'];

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        unset($map['004-disposition']);
        unset($map['003-showpage']);
        //allowed extensions
        $map['002-file']['extraConstructArgs'][] = ['gif','png','jpg','jpeg','bmp'];
        //max size
        $map['002-file']['extraConstructArgs'][] = 20*1024*1024;
        return $map;
    }

    public function fileUrl($uniqid=null, $preset=false)
    {
        if ($uniqid === null) {
            $fs = $this->factory->cms()->helper('filestore');
            $files = $fs->list($this, static::PATH);
            if (!$files) {
                return null;
            }
            $f = array_pop($files);
            $uniqid = $f->uniqid();
        }
        $args = ['uniqid' => $uniqid];
        if ($preset) {
            $args['preset'] = $preset;
        }
        return $this->url(
            'file',
            $args
        );
    }

    public function tagEmbed(array $args = [])
    {
    }
}
