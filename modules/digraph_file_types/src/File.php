<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_file_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class File extends Noun
{
    const FILESTORE = true;
    const PATH = 'filefield';

    public function handle_display(&$package)
    {
        $fs = $this->factory->cms()->helper('filestore');
        $files = $fs->list($this, static::PATH);
        if (!$files) {
            $this->factory->cms()->helper('notifications')->error(
                $this->factory->cms()->helper('strings')->string('file.notifications.nofile')
            );
            return;
        }
        $f = array_pop($files);
        //display metadata page if requested, or if user can edit
        if ($this['file.showpage'] || $this->isEditable()) {
            //show notice for users who are only seeing metadata page because
            //they have edit permissions
            if (!$this['file.showpage']) {
                $this->factory->cms()->helper('notifications')->notice(
                    $this->factory->cms()->helper('strings')->string('file.notifications.editbypass')
                );
            }
            echo $f->metacard();
            //dislay metadata page and return so that we skip outputting file
            return;
        }
        //there is a file, send it to the browser
        $fs->output($package, $f);
    }

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '002-file' => [
                // 'field' => 'filestore',
                'label' => $s->string('forms.file.upload_single.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileStoreFieldSingle',
                'required' => true,
                'extraConstructArgs' => [static::PATH]
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
