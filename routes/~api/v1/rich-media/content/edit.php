<!-- media-editor-force-wide -->
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\ContentRichMedia;

/** @var ContentRichMedia */
$media = RichMedia::get(Context::arg('edit'));

$form = new FormWrapper('edit-rich-media-' . Context::arg('edit') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Save changes');

$name = (new Field('Content name'))
    ->setDefault($media->name())
    ->setRequired(true);

$content = (new RichContentField('Content', Context::arg('uuid'), true))
    ->setDefault($media->content())
    ->setID('edit-content')
    ->setRequired(true);

$form
    ->addChild($name)
    ->addChild($content)
    ->addCallback(function () use ($media, $name, $content) {
        // save name
        $media->name($name->value());
        // save content
        $media->content($content->value());
        // insert and redirect
        $media->update();
        $url = Context::url();
        $url->unsetArg('edit');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;
