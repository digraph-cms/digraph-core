<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use Digraph\DSO\Noun;
use Digraph\FileStore\FileStoreFile;

class Page extends Noun
{
    const ROUTING_NOUNS = ['page'];
    const FILESTORE = true;
    const FILESTORE_PATH = 'filefield';
    const FILESTORE_FILE_CLASS = FileStoreFile::class;

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '550-files' => [
                'label' => $s->string('forms.file.upload_multi.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
                'required' => true,
                'extraConstructArgs' => [static::FILESTORE_PATH]
            ]
        ];
    }
}
