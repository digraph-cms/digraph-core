<h1>Delete page</h1>
<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

echo '<div id="recursive-delete-interface" class="navigation-frame navigation-frame--stateless" data-target="_frame">';

$page = Context::page();
Notifications::printError('Are you sure you would like to delete <strong>' . $page->name() . '</strong>? This action cannot be undone.');

// has child pages, so remind user of that
if ($count = Graph::childEdges($page->uuid())->count()) {
    Notifications::printError("$count child pages and everything under them will also be deleted.");
}

echo new SingleButton(
    'Confirm deletion',
    function () {
        throw new RedirectException(
            new URL('_delete.html?csrf=' . Cookies::csrfToken('delete_' . Context::pageUUID()))
        );
    },
    ['button--danger']
);

echo '</div>';
