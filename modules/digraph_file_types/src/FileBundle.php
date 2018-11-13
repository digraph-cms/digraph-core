<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_file_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;
use Digraph\FileStore\FileStoreFile;

class FileBundle extends Noun
{
    const ROUTING_NOUNS = ['filebundle'];
    const FILESTORE = true;
    const FILESTORE_PATH = 'filefield';
    const FILESTORE_FILE_CLASS = FileStoreFile::class;

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '002-file' => [
                'label' => $s->string('forms.file.upload_multi.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldMulti',
                'required' => true,
                'extraConstructArgs' => [static::FILESTORE_PATH]
            ]
        ];
    }
}
