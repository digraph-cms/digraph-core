<!-- media-editor-force-wide -->
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTML\ResponsivePicture;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\ImageRichMedia;
use DigraphCMS\UI\Notifications;

// check media exists, redirect if not
/** @var ImageRichMedia */
$media = RichMedia::get(Context::arg('edit'));

// display current file
try {
    $preview = new ResponsivePicture($media->file()->image(), 'Preview of uploaded image');
    $preview->setExpectedWidth(50);
    $preview->setMaxHeight(30);
    echo $preview;
} catch (\Throwable $th) {
    Notifications::printError("Exception rendering preview");
}

// form for editing settings or replacing file
$form = new FormWrapper('edit-rich-media-' . Context::arg('file') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Save changes');

$file = (new Field('Replace image file', new UploadSingle()))
    ->setRequired(false)
    ->addTip('Leave blank to continue using the existing file');

$name = (new Field('Image name'))
    ->addTip('Leave blank to use the uploaded filename')
    ->addTip('If entered, this field will be used as the download link text instead of the filename');

$alt = (new Field('Alt text'))
    ->setRequired(true)
    ->setDefault($media['alt']);

$caption = (new RichContentField('Caption', null, true))
    ->addClass('rich-content-editor--mini')
    ->setDefault($media->caption());

$form
    ->addChild($file)
    ->addChild($name)
    ->addChild($alt)
    ->addChild($caption)
    ->addCallback(function () use ($media, $file, $name, $alt, $caption) {
        // set up new media and its file
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
        // save alt and caption
        $media->setCaption($caption->value());
        $media['alt'] = $alt->value();
        // update and close editing interface
        $media->update();
        $url = Context::url();
        $url->unsetArg('edit');
        throw new RedirectException($url);
    });

echo $form;
