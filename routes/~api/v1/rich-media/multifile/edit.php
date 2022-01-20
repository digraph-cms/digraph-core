<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\OrderingInput;
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

$order = (new Field('Current files', new OrderingInput()));
foreach ($media->files() as $file) {
    $order->input()->addLabel($file->uuid(), $file->filename());
}
$order->setDefault($media['files']);
$order->input()->setAllowDeletion(true);

$name = (new Field('Media name'))
    ->setDefault($media->name())
    ->setRequired(true);

$files = (new Field('Upload more files', new UploadMulti()))
    ->addTip('You will be given the option to reorder files in the next step.');

$options = (new CheckboxListField(
    'Options',
    [
        'single' => 'List and allow downloading individual files'
    ]
))
    ->setDefault($media['options'] ?? []);

$meta = (new CheckboxListField(
    'Display metadata',
    [
        'uploader' => 'Update user',
        'upload_date' => 'Update date',
    ]
))
    ->setDefault($media['meta'] ?? []);

$form
    ->addChild($name)
    ->addChild($order)
    ->addChild($files)
    ->addChild($options)
    ->addChild($meta)
    ->addCallback(function () use ($media, $files, $name, $order, $options, $meta) {
        // set name
        $media->name($name->value());
        // set options
        unset($media['options']);
        $media['options'] = $options->value();
        // set meta
        unset($media['meta']);
        $media['meta'] = $meta->value();
        // delete files
        $deleted = array_diff($media['files'], $order->value());
        foreach ($deleted as $f) {
            Filestore::get($f)->delete();
        }
        // set remaining file order
        unset($media['files']);
        $media['files'] = $order->value();
        // add new files to list
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
        } else {
            // otherwise close editing interface
            $url = Context::url();
            $url->unsetArg('edit');
            throw new RedirectException($url);
        }
    });

echo $form;
