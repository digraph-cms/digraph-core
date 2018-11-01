<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_file_types;

use Digraph\DSO\Noun;
use HtmlObjectStrings\A;

class File extends Noun
{
    const PATH = 'filefield';

    public function handle_display(&$package)
    {
        $fs = $this->factory->cms()->helper('filestore');
        $files = $fs->list($this, static::PATH);
        $f = array_pop($files);
        var_dump($f);
    }

    public function handle_download(&$package)
    {
        $f = $package['url.args.f'];
        if (!$f) {
            $package->error(404);
            return;
        }
        //ask filestore for matching files -- note that get() searches by filename OR uniqid
        $fs = $this->factory->cms()->helper('filestore');
        $strings = $this->factory->cms()->helper('strings');
        $files = $fs->get($this, static::PATH, $f);
        //produce 300 error if multiple results come up
        if (count($files) > 1) {
            $package->error(300, 'Multiple files match');
            $package['response.300'] = [];
            foreach ($files as $f) {
                $package->push('response.300', [
                    'link' => $this->link(
                        $f->name().' uploaded '.$strings->datetimeHTML($f->time()),//link text
                        'download',//link verb
                        ['f'=>$f->uniqid()],//use file uniquid
                        true//canonical URL
                    )
                ]);
            }
            return;
        }
        //if everything is good, feed through the file
        $f = array_pop($files);
        $package->makeMediaFile($f->name());
        $package['response.readfile'] = $f->path();
    }

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        return [
            '002-file' => [
                // 'field' => 'filestore',
                'label' => $s->string('forms.file.upload_single.container'),
                'class' => 'Digraph\\Forms\\Fields\\FileField',
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
