<!-- media-editor-force-wide -->
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\RichMedia\Types\ImageRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add image');

$file = (new Field('Upload image file', new UploadSingle()))
    ->setRequired(true);

$name = (new Field('Image name'))
    ->addTip('Leave blank to use the uploaded filename')
    ->addTip('If entered, this field will be used as the download link text instead of the filename');

$alt = (new Field('Alt text'))
    ->setRequired(true);

$caption = (new RichContentField('Caption', null, true))
    ->addClass('rich-content-editor--mini');

$form
    ->addChild($file)
    ->addChild($name)
    ->addChild($alt)
    ->addChild($caption)
    ->addCallback(function () use ($file, $name, $alt, $caption) {
        // set up new media and its file
        $media = new ImageRichMedia([], ['page_uuid' => Context::arg('uuid')]);
        $media['file'] = $file->input()->filestore($media->uuid())->uuid();
        // set up name
        if ($name->value()) {
            $media->name($name->value());
        } else {
            $media->name($media->file()->filename());
        }
        // save alt and caption
        $media->setCaption($caption->value());
        $media['alt'] = $alt->value();
        // insert and redirect
        $media->insert();
        $url = Context::url();
        $url->unsetArg('add');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;
