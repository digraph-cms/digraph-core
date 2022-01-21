<!-- media-editor-force-wide -->
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\TableInput;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\Types\FileRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add media');

$name = (new Field('Media name'))
    ->addTip('Used only to identify this table in the media browser');

$table = (new Field('Table content',new TableInput()));

$form
    ->addChild($name)
    ->addChild($table)
    ->addCallback(function () use ($name) {
        // set up new media and its file
        $media = new FileRichMedia([], ['page_uuid' => Context::arg('uuid')]);
        // set up name
        $media->name($name->value());
        // insert and redirect
        $media->insert();
        $url = Context::url();
        $url->unsetArg('add');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;
