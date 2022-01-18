<?php

use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadMulti;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\Types\MultiFileRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add media');

$name = (new Field('Media name'))
    ->setRequired(true);

$files = (new Field('Upload files', new UploadMulti()))
    ->setRequired(true)
    ->addTip('You will be given the option to reorder files in the next step.');

$form
    ->addChild($name)
    ->addChild($files)
    ->addCallback(function () use ($files, $name) {
        $media = new MultiFileRichMedia([], ['page_uuid' => Context::arg('uuid')]);
        // set name
        $media->name($name->value());
        // set files
        $media['files'] = array_map(
            function (FilestoreFile $file): string {
                return $file->uuid();
            },
            $files->input()->filestore($media->uuid())
        );
        // insert and redirect
        $media->insert();
        $url = Context::url();
        $url->unsetArg('add');
        $url->arg('edit', $media->uuid());
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;
