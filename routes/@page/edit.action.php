<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FORM;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;

Cookies::required(['system', 'csrf']);

$page = Context::page();

$form = new FORM('edit-'.$page->uuid());
$form->button()->setText('Save changes');

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
    DB::beginTransaction();
    // save changes to page
    $page->name($name->value());
    $page->richContent('body', $content->value());
    $page->update();
    // dispatch pageedited event
    Dispatcher::dispatchEvent('onPagEdited', [$page]);
    // commit and redirect
    DB::commit();
    Notifications::flashConfirmation('Changes saved to ' . $page->url()->html());
    throw new RefreshException();
}
echo $form;
