<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\UI\Toolbars\ToolbarSeparator;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;

Context::response()->template('iframe.php');
$page = Pages::get(Context::arg('uuid'));
$action = Context::arg('action');

echo '<div id="' . Context::arg('frame') . '">';

if ($action && !preg_match('/[^a-z0-9\-\_]/', $action)) {
    Router::include("$action.php");
    echo new ToolbarSpacer;
    echo (new ToolbarLink('Cancel', 'close', null, new URL('&action=')))
        ->addClass('toolbar__button--warning');
    echo '</div>';
}

$buttons = [
    (new ToolbarLink('Heading', 'heading', 'markdownToggleHeading'))
        ->setShortcut('Ctrl-H'),
    (new ToolbarLink('Heading (reversed)', 'heading', 'markdownToggleHeadingReverse'))
        ->setShortcut('Ctrl-Shift-H')
        ->setStyle('display', 'none'),
    (new ToolbarLink('Bold', 'bold', 'markdownToggleBold'))
        ->setShortcut('Ctrl-B'),
    (new ToolbarLink('Italic', 'italic', 'markdownToggleItalic'))
        ->setShortcut('Ctrl-I'),
    (new ToolbarLink('Strikethrough', 'strikethrough', 'markdownToggleStrikethrough'))
        ->setShortcut('Ctrl-Shift-S'),
    new ToolbarSeparator,
    (new ToolbarLink('Bullet list', 'list-bullet', 'markdownToggleBulletList'))
        ->setShortcut('Ctrl-L'),
    (new ToolbarLink('Numbered list', 'list-numbered', 'markdownToggleNumberedList'))
        ->setShortcut('Ctrl-Shift-L'),
    (new ToolbarLink('Block quote', 'quote', 'markdownToggleQuote'))
        ->setShortcut('Ctrl-Shift-Q'),
    (new ToolbarLink('Code', 'code', 'markdownToggleCodeBlock'))
        ->setShortcut('Ctrl-Shift-C'),
    new ToolbarSeparator,
    (new ToolbarLink('Link to any URL', 'link', null, new URL('&action=weblink')))
        ->setShortcut('Ctrl-Shift-K'),
    (new ToolbarLink('Link to a page', 'pages', null, new URL('&action=link')))
        ->setShortcut('Ctrl-K'),
    (new ToolbarLink('Quick media insert', 'media', null, new URL('&action=media')))
        ->setShortcut('Ctrl-M'),
    (new ToolbarLink('Link to a user', 'person', null, new URL('&action=user')))
        ->setShortcut('Ctrl-U'),
    new ToolbarSeparator,
    (new ToolbarLink('Toggle comment', 'hide', 'toggleComment', null))
        ->setShortcut('Ctrl-/ '),
    new ToolbarSeparator,
    new ToolbarSpacer,
    new ToolbarSeparator,
    (new ToolbarLink('Undo', 'undo', 'undo'))
        ->setShortcut('Ctrl-Z'),
    (new ToolbarLink('Redo', 'redo', 'redo'))
        ->setShortcut('Ctrl-Y')
];
Dispatcher::dispatchEvent('onRichMediaToolbar', [&$buttons]);
if ($page) {
    Dispatcher::dispatchEvent('onRichMediaToolbar_' . $page->class(), [&$buttons]);
}
foreach ($buttons as $button) {
    echo $button;
}

echo '</div>';
