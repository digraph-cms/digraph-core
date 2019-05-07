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
    const SLUG_ENABLED = false;
    const PUBLISH_CONTROL = false;

    public function formMap(string $action) : array
    {
        $map = parent::formMap($action);
        $s = $this->factory->cms()->helper('strings');
        $map['file'] = [
            'weight' => 250,
            'label' => $s->string('forms.file.upload_single.container'),
            'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldSingle',
            'required' => true,
            'extraConstructArgs' => [static::FILESTORE_PATH]
        ];
        $map['showpage'] = [
            'weight' => 251,
            'field' => 'file.showpage',
            'label' => $s->string('forms.file.showpage'),
            'class' => 'Formward\Fields\Checkbox'
        ];
        return $map;
    }
}
