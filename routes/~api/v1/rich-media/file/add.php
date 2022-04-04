<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\Types\FileRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add media');

$file = (new Field('Upload file', new UploadSingle()))
    ->setRequired(true);

$name = (new Field('Media name'))
    ->addTip('Leave blank to use the uploaded filename')
    ->addTip('If entered, this field will be used as the download link text instead of the filename');

$meta = (new CheckboxListField(
    'Display metadata',
    [
        'size' => 'Filesize',
        'uploader' => 'Uploading user',
        'upload_date' => 'Upload date',
        'md5' => 'MD5 hash'
    ]
))
    ->setDefault(['size', 'uploader', 'upload_date']);

$form
    ->addChild($file)
    ->addChild($name)
    ->addChild($meta)
    ->addCallback(function () use ($file, $name, $meta) {
        // set up new media and its file
        $media = new FileRichMedia([], ['parent' => Context::arg('uuid')]);
        $media['file'] = $file->input()->filestore($media->uuid())->uuid();
        // set up name
        if ($name->value()) {
            $media->name($name->value());
        } else {
            $media->name($media->file()->filename());
        }
        // metadata
        $media['meta'] = $meta->value();
        // insert and redirect
        $media->insert();
        $url = Context::url();
        $url->unsetArg('add');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;
