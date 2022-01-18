<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\RichContent\ToolbarButton;
use DigraphCMS\RichContent\ToolbarSeparator;
use DigraphCMS\RichContent\ToolbarSpacer;
use DigraphCMS\URL\URL;

Context::response()->template('iframe.php');
$page = Pages::get(Context::arg('uuid'));
$action = Context::arg('action');

echo '<div id="' . Context::arg('frame') . '">';

if ($action && !preg_match('/[^a-z0-9\-\_]/', $action)) {
    Router::include("$action.php");
    echo new ToolbarSpacer();
    echo (new ToolbarButton('Cancel', 'close', null, new URL('&action=')))
        ->addClass('rich-content-editor__toolbar__button--warning');
    echo '</div>';
}

$buttons = [
    new ToolbarButton('Page link', 'link', null, new URL('&action=link')),
    new ToolbarButton('Web link', 'web', null, new URL('&action=weblink')),
    new ToolbarSpacer(),
    (new ToolbarButton('Undo', 'undo', 'undo'))
        ->addClass('rich-content-editor__toolbar__button--undo-button')
        ->setShortcut('Ctrl-Z'),
    (new ToolbarButton('Redo', 'redo', 'redo'))
        ->addClass('rich-content-editor__toolbar__button--redo-button')
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
