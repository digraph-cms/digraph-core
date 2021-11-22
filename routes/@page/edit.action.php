<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Notifications;
use Formward\Fields\Input;

Cookies::required(['system', 'csrf']);

$page = Context::page();

$form = new Form('Edit page');

$form['name'] = new Input('Page name');
$form['name']->default($page->name());
$form['name']->required(true);
$form['name']->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$form['content'] = new RichContentField('Body content');
$form['content']->default($page->richContent('body'));
$form['content']->required(true);

if ($form->handle()) {
    $page->name($form['name']->value());
    $page->richContent('body', $form['content']->value());
    $page->update();
    Notifications::flashConfirmation('Changes saved to ' . $page->url()->html());
    throw new RefreshException();
}
echo $form;
