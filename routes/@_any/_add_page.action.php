<?php

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;

Cookies::required(['system', 'csrf']);
Context::ensureUUIDArg(Pages::class);

$page = Context::page();

$name = (new Field('Page name'))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$content = (new RichContentField('Body content'))
    ->setDefault('# [page_name]')
    ->setPageUuid(Context::arg('uuid'))
    ->setRequired(true);

$form = (new FormWrapper('add-' . Context::arg('uuid')))
    ->addChild($name)
    ->addChild($content)
    ->addCallback(function () use ($name, $content) {
        // insert page
        $page = new Page(
            [],
            [
                'uuid' => Context::arg('uuid')
            ]
        );
        $page->name($name->value());
        $page->richContent('body', $content->value());
        // insert with parent link to current context page
        $page->insert(Context::page()->uuid());
        // redirect
        Notifications::flashConfirmation('Page created: ' . $page->url()->html());
        throw new RedirectException($page->url_edit());
    });
$form->button()->setText('Create page');

echo $form;
