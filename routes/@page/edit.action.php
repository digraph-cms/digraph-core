<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Forms\EditorField;
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

$form['content'] = new EditorField('Page content');
$form['content']->default(json_encode($page['content'] ?? []));

if ($form->handle()) {
    $page->name($form['name']->value());
    $page['content'] = json_decode($form['content']->value());
    $page->update();
    Notifications::flashConfirmation('Changes saved to ' . $page->url()->html());
    throw new RefreshException();
}
echo $form;
