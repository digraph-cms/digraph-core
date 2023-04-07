<?php

use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichContent\RichContentField;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;

Cookies::required(['system', 'csrf']);
Context::ensureUUIDArg(Pages::class);

echo "<h1>Add disconnected page</h1>";
echo "<p>This form creates a page that does not have a parent. It is meant for creating pages that are disconnected from the main site map.</p>";

$name = (new Field('Page name'))
    ->setRequired(true)
    ->addTip('The name to be used when referring or linking to this page from elsewhere on the site.');

$content = (new RichContentField('Body content'))
    ->setDefault('# [page_name]')
    ->setPageUuid(Context::arg('uuid'))
    ->setRequired(true);

$url = (new Field('Set URL pattern'))
    ->setRequired(true)
    ->setDefault('[name]')
    ->addTip('Add a leading slash to make pattern relative to site root, otherwise it will be relative to the page\'s parent URL (should you create a link that gives this page a parent URL).');

$form = (new FormWrapper('add-' . Context::arg('uuid')))
    ->addChild($name)
    ->addChild($content)
    ->addChild($url)
    ->addCallback(function () use ($name, $content, $url) {
        DB::beginTransaction();
        // insert page
        $page = new Page(
            [],
            [
                'uuid' => Context::arg('uuid')
            ]
        );
        $page->slugPattern($url->value());
        $page->name($name->value());
        $page->richContent('body', $content->value());
        $page->insert();
        // commit and redirect
        DB::commit();
        Notifications::flashConfirmation('Page created: ' . $page->url()->html());
        throw new RedirectException($page->url_edit());
    });
$form->button()->setText('Create page');

echo $form;
