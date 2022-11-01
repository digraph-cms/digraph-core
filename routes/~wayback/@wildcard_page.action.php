<?php

use DigraphCMS\Context;
use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\WaybackMachine;

Context::response()->enableCache();
Context::response()->headers()->set('X-Robots-Tag', 'noindex');

$storage = new DatastoreGroup('wayback', 'page');
$result = $storage->get(Context::url()->actionSuffix());

if (!$result) throw new HttpError(404);

// bounce straight to URL if it's actually up now
if (WaybackMachine::check('http://'.$result->data()['original_url'])) {
    throw new ArbitraryRedirectException('http://' . $result->data()['original_url']);
    return;
}

// add context to breadcrumb
if ($result->data()['context']) {
    Breadcrumb::parent(new URL($result->data()['context']));
}

// render tempalte
echo Templates::render(
    'content/wayback.php',
    ['wb_data' => $result->data()]
);

// add management URL
$manageUrl = new URL('_manage.html');
$manageUrl->arg('url', $result->data()['original_url']);
$manageUrl->arg('context', $result->data()['context']);
$manageUrl->setName('Manage this link');
ActionMenu::addContextAction($manageUrl);
