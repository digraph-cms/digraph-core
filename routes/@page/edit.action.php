<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FORM;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;

Cookies::required(['system', 'csrf']);

$page = Context::page();

$form = new FORM('edit-'.$page->uuid());

$name = new Field('Page name');
$name->setDefault($page->name())
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$content = new RichContentField('Body content');
$content->setDefault($page->richContent('body'))
    ->setRequired(true);

$form->addChild($name);
$form->addChild($content);

if ($form->ready()) {
    $page->name($name->value());
    $page->richContent('body', $content->value());
    $page->update();
    Notifications::flashConfirmation('Changes saved to ' . $page->url()->html());
    throw new RefreshException();
}
echo $form;
