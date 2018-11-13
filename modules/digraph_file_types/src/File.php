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

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '002-file' => [
                'label' => $s->string('forms.file.upload_single.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldSingle',
                'required' => true,
                'extraConstructArgs' => [static::FILESTORE_PATH]
            ],
            '003-showpage' => [
                'field' => 'file.showpage',
                'label' => $s->string('forms.file.showpage'),
                'class' => 'Formward\Fields\Checkbox'
            ],
            '004-disposition' => [
                'field' => 'file.disposition',
                'label' => $s->string('forms.file.disposition'),
                'class' => 'Formward\Fields\Checkbox'
            ]
        ];
    }
}
