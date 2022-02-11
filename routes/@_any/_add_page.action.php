<?php

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;

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

Cookies::required(['system', 'csrf']);

$page = Context::page();

$name = (new Field('Page name'))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$content = (new RichContentField('Body content'))
    ->setPageUuid(Context::arg('uuid'))
    ->setRequired(true);

$form = (new FormWrapper('add-' . Context::arg('uuid')))
    ->addChild($name)
    ->addChild($content)
    ->addCallback(function () use ($name, $content) {
        DB::beginTransaction();
        // insert page
        $page = new Page(
            [],
            [
                'uuid' => Context::arg('uuid')
            ]
        );
        $page->name($name->value());
        $page->richContent('body', $content->value());
        $page->insert();
        // create edge to parent
        Pages::insertLink(Context::page()->uuid(), $page->uuid());
        // commit and redirect
        DB::commit();
        Notifications::flashConfirmation('Page created: ' . $page->url()->html());
        throw new RedirectException($page->url_edit());
    });
$form->button()->setText('Create page');

echo $form;
