<!-- media-editor-force-wide -->
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\RichMedia\Types\AsideRichMedia;

$form = new FormWrapper('add-rich-media-' . Context::arg('add') . '-' . Context::arg('frame'));
$form->form()->setData('target', Context::arg('frame'));
$form->button()->setText('Add aside');

$name = (new Field('Aside name'))
    ->setRequired(true);

$content = (new RichContentField('Content', Context::arg('uuid'), true))
    ->setID('add-aside')
    ->setRequired(true);

$form
    ->addChild($name)
    ->addChild($content)
    ->addCallback(function () use ($name, $content) {
        $media = new AsideRichMedia([], ['page_uuid' => Context::arg('uuid')]);
        // save name
        $media->name($name->value());
        // save content
        $media->content($content->value());
        // insert and redirect
        $media->insert();
        $url = Context::url();
        $url->unsetArg('add');
        $url->arg('_tab_tab', 'page');
        throw new RedirectException($url);
    });

echo $form;
