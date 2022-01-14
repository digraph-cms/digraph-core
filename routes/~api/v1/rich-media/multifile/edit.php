<?php

use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadMulti;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\MultiFileRichMedia;

/** @var MultiFileRichMedia */
$media = RichMedia::get(Context::arg('edit'));

$form = new FormWrapper('add-rich-media-' . Context::arg('edit') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Save changes');

// TODO: reordering/deleting field

$name = (new Field('Media name'))
    ->setDefault($media->name())
    ->setRequired(true);

$files = (new Field('Upload more files', new UploadMulti()))
    ->addTip('You will be given the option to reorder files in the next step.');

$form
    ->addChild($name)
    ->addChild($files)
    ->addCallback(function () use ($media, $files, $name) {
        // set name
        $media->name($name->value());
        // add new files
        $media['files'] = array_merge(
            $media['files'],
            array_map(
                function (FilestoreFile $file): string {
                    return $file->uuid();
                },
                $files->input()->filestore($media->uuid())
            )
        );
        // update and redirect
        $media->update();
        if ($files->value()) {
            // if new files were added, refresh to allow reordering
            throw new RefreshException();
        }else {
            // otherwise close editing interface
            $url = Context::url();
            $url->unsetArg('edit');
            throw new RedirectException($url);
        }
    });

echo $form;
