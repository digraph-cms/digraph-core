<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;

Cookies::required(['system', 'csrf']);

$page = Context::page();

$name = (new Field('Page name'))
    ->setDefault($page->name(null, true))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$content = (new RichContentField('Body content'))
    ->setPageUuid($page->uuid())
    ->setDefault($page->richContent('body'))
    ->setRequired(true);

$form = (new FormWrapper('edit-' . $page->uuid()))
    ->addChild($name)
    ->addChild($content);
$form->button()
    ->setText('Save changes');

if ($form->ready()) {
    DB::beginTransaction();
    // save changes to page
    $page->name($name->value());
    $page->richContent('body', $content->value());
    $page->update();
    // commit and redirect
    DB::commit();
    Notifications::flashConfirmation('Changes saved to ' . $page->url()->html());
    throw new RefreshException();
}
echo $form;
