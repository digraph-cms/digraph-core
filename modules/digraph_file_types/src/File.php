<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_file_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;
use Digraph\FileStore\FileStoreFile;

class File extends Noun
{
    const ROUTING_NOUNS = ['file'];
    const FILESTORE = true;
    const FILESTORE_PATH = 'filefield';
    const FILESTORE_FILE_CLASS = FileStoreFile::class;
    const SLUG_ENABLED = true;

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            'file' => [
                'weight' => 502,
                'label' => $s->string('forms.file.upload_single.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldSingle',
                'required' => true,
                'extraConstructArgs' => [static::FILESTORE_PATH]
            ],
            'showpage' => [
                'weight' => 503,
                'field' => 'file.showpage',
                'label' => $s->string('forms.file.showpage'),
                'class' => 'Formward\Fields\Checkbox'
            ]
        ];
    }
}
