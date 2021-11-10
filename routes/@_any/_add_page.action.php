<?php

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Editor\Editor;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Forms\EditorField;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Notifications;
use Formward\Fields\Input;

Cookies::required(['system', 'csrf']);

// ensure we have a UUID in the parameters
if (!Context::arg('uuid')) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// validate parameter UUID
if (!Digraph::validateUUID(Context::arg('uuid') ?? '')) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

// ensure parameter UUID doesn't already exist
if (Pages::exists(Context::arg('uuid'))) {
    $url = Context::url();
    $url->arg('uuid', Digraph::uuid());
    throw new RedirectException($url);
}

$form = new Form('Add page');

$form['name'] = new Input('Page name');
$form['name']->required(true);
$form['name']->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

Editor::contextUUID(Context::arg('uuid'));
$form['content'] = new EditorField('Page content');

if ($form->handle()) {
    DB::beginTransaction();
    // insert page
    $page = new Page(
        [],
        [
            'uuid' => Context::arg('uuid')
        ]
    );
    $page->name($form['name']->value());
    $page['content'] = json_decode($form['content']->value());
    $page->insert();
    // create edge to parent
    Pages::insertLink(Context::page()->uuid(), $page->uuid());
    // dispatch pagecreated event, this is where we set slug from pattern, we
    // do this manually here so that duplicate non-parent slugs don't get made
    Dispatcher::dispatchEvent('onPageCreated', [$page]);
    // notify and redirect
    DB::commit();
    Notifications::flashConfirmation('Added ' . $page->url()->html());
    throw new RedirectException($page->url_edit());
}
echo $form;
