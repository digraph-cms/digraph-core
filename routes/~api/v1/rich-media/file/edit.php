<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\FileRichMedia;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;

// check media exists, redirect if not
/** @var FileRichMedia */
$media = RichMedia::get(Context::arg('edit'));

// display current file
$file = $media->file();
echo "<div class='card card--neutral'>";
printf(
    '<strong><a href="%s" target="_blank">%s</a></strong><br>',
    $file->url(),
    $file->filename()
);
printf('Filesize %s<br>',Format::filesize($file->bytes()));
printf('Uploaded by %s<br>', $file->createdBy());
printf('Uploaded %s<br>', Format::datetime($file->created()));
printf('MD5 <code>%s</code>', $file->hash());
echo "</div>";

// form for editing settings or uploading new file
$form = new FormWrapper('edit-rich-media-' . Context::arg('file') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Save changes');

$file = (new Field('Replace current file', new UploadSingle()))
    ->setRequired(false)
    ->addTip('Use this field to replace the uploaded file or leave it blank to keep the current file');

$name = (new Field('Media name'))
    ->setDefault($media->name())
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
    ->setDefault($media['meta'] ?? []);

$form
    ->addChild($file)
    ->addChild($name)
    ->addChild($meta)
    ->addCallback(function () use ($media, $file, $name, $meta) {
        // update file
        if ($file->value()) {
            $media->file()->delete();
            $media['file'] = $file->input()->filestore($media->uuid())->uuid();
        }
        // set up name
        if ($name->value()) {
            $media->name($name->value());
        } else {
            $media->name($media->file()->filename());
        }
        // metadata
        $media['meta'] = $meta->value();
        // insert and close editing interface
        $media->update();
        $url = Context::url();
        $url->unsetArg('edit');
        throw new RedirectException($url);
    });

echo $form;
