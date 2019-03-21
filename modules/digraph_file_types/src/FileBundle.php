<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_file_types;

use Digraph\DSO\Noun;
use Digraph\FileStore\FileStoreFile;

class FileBundle extends Noun
{
    const ROUTING_NOUNS = ['file-bundle'];
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
                'label' => $s->string('forms.file.upload_multi.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
                'required' => true,
                'extraConstructArgs' => [static::FILESTORE_PATH]
            ],
            'gallery' => [
                'weight' => 503,
                'field' => 'file-bundle.gallery',
                'label' => $s->string('forms.file-bundle.gallery'),
                'class' => 'Formward\Fields\Checkbox'
            ]
        ];
    }
}
