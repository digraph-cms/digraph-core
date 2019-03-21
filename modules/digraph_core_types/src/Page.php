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
    const SLUG_ENABLED = true;

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        $s = $this->factory->cms()->helper('strings');
        $map['files'] = [
            'weight' => 550,
            'label' => $s->string('forms.file.upload_multi.container'),
            'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
            'extraConstructArgs' => [static::FILESTORE_PATH]
        ];
        return $map;
    }
}
