<?php

use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadMulti;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\Types\ZipRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add media');

$name = (new Field('Media name'))
    ->setRequired(true)
    ->addTip('Used as the label when embedding, and as the filename of the Zip download');

$files = (new Field('Upload files', new UploadMulti()))
    ->setRequired(true)
    ->addTip('You will be given the option to reorder files in the next step.');

$options = (new CheckboxListField(
    'Options',
    [
        'single' => 'List and allow downloading individual files'
    ]
));

$meta = (new CheckboxListField(
    'Display metadata',
    [
        'uploader' => 'Update user',
        'upload_date' => 'Update date',
    ]
));

$form
    ->addChild($name)
    ->addChild($files)
    ->addChild($options)
    ->addCallback(function () use ($files, $name, $options) {
        $media = new ZipRichMedia([], ['parent' => Context::arg('uuid')]);
        // set name
        $media->name($name->value());
        // set options
        $media['options'] = $options->value();
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
