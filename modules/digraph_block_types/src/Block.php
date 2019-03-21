<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_block_types;

use Digraph\DSO\Noun;
use Digraph\FileStore\FileStoreFile;

class Block extends Noun
{
    const ROUTING_NOUNS = ['block'];
    const FILESTORE = true;
    const FILESTORE_PATH = 'filefield';
    const FILESTORE_FILE_CLASS = FileStoreFile::class;

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            'digraph_title' => false,
            'files' => [
                'weight' => 550,
                'label' => $s->string('forms.file.upload_multi.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
                'extraConstructArgs' => [static::FILESTORE_PATH]
            ]
        ];
    }
}
