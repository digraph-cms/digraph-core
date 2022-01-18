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
    new ToolbarLink('Page link', 'link', null, new URL('&action=link')),
    new ToolbarLink('Web link', 'web', null, new URL('&action=weblink')),
    new ToolbarSpacer(),
    (new ToolbarLink('Undo', 'undo', 'undo'))
        ->setShortcut('Ctrl-Z'),
    (new ToolbarLink('Redo', 'redo', 'redo'))
        ->setShortcut('Ctrl-Shift-Z')
];
Dispatcher::dispatchEvent('onRichMediaToolbar', [&$buttons, $frame, $page]);
if ($page) {
    Dispatcher::dispatchEvent('onRichMediaToolbar_' . $page->class(), [&$buttons, $frame, $page]);
}
foreach ($buttons as $button) {
    echo $button;
}

echo '</div>';
