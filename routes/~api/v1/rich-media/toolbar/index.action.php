<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;

Context::response()->template('iframe.php');
$page = Pages::get(Context::arg('uuid'));
$action = Context::arg('action');

echo '<div id="' . Context::arg('frame') . '">';

if ($action && !preg_match('/[^a-z0-9\-\_]/', $action)) {
    Router::include("$action.php");
    echo new ToolbarSpacer();
    echo (new ToolbarLink('Cancel', 'close', null, new URL('&action=')))
        ->addClass('toolbar__button--warning');
    echo '</div>';
}

$buttons = [
    new ToolbarLink('Link to a page', 'page', null, new URL('&action=link')),
    new ToolbarLink('Link to a URL', 'link', null, new URL('&action=weblink')),
    new ToolbarLink('Quick media insert', 'media', null, new URL('&action=media')),
    new ToolbarSpacer(),
    (new ToolbarLink('Undo', 'undo', 'undo'))
        ->setShortcut('Ctrl-Z'),
    (new ToolbarLink('Redo', 'redo', 'redo'))
        ->setShortcut('Ctrl-Shift-Z')
];
Dispatcher::dispatchEvent('onRichMediaToolbar', [&$buttons]);
if ($page) {
    Dispatcher::dispatchEvent('onRichMediaToolbar_' . $page->class(), [&$buttons]);
}
foreach ($buttons as $button) {
    echo $button;
}

echo '</div>';
