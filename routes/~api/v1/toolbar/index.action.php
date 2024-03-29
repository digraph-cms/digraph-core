<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\UI\Toolbars\ToolbarSeparator;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

Context::response()->template('chromeless.php');

$page = Context::arg('uuid') ? Pages::get(Context::arg('uuid')) : null;
$action = Context::arg('action');
$only = Context::arg('only') ? explode(',', Context::arg('only')) : [];

echo '<div id="' . Context::arg('frame') . '">';

if ($action && !preg_match('/[^a-z0-9\-\_]/', $action)) {
    Router::include("$action.php");
    echo new ToolbarSpacer;
    echo (new ToolbarLink('Cancel', 'close', null, new URL('&action=')))
        ->addClass('toolbar__button--warning');
    echo '</div>';
}

$buttons = [];

if (!$only || in_array('format', $only)) {
    $buttons[] = [
        (new ToolbarLink('Heading', 'heading', 'markdownToggleHeading'))
            ->setShortcut('Ctrl-H'),
        (new ToolbarLink('Heading (reversed)', 'heading', 'markdownToggleHeadingReverse'))
            ->setShortcut('Ctrl-Shift-H')
            ->setStyle('display', 'none'),
        (new ToolbarLink('Bold', 'bold', 'markdownToggleBold'))
            ->setShortcut('Ctrl-B'),
        (new ToolbarLink('Italic', 'italic', 'markdownToggleItalic'))
            ->setShortcut('Ctrl-I'),
        (new ToolbarLink('Strike', 'strikethrough', 'markdownToggleStrikethrough'))
            ->setShortcut('Ctrl-Shift-S'),
        (new ToolbarLink('Highlight', 'highlight', 'markdownToggleHighlight'))
            ->setShortcut('Ctrl-Shift-M'),
    ];
}
if (!$only || in_array('blocks', $only)) {
    $buttons[] = [
        (new ToolbarLink('Bullet list', 'list-bullet', 'markdownToggleBulletList'))
            ->setShortcut('Ctrl-L'),
        (new ToolbarLink('Numbered list', 'list-numbered', 'markdownToggleNumberedList'))
            ->setShortcut('Ctrl-Shift-L'),
        (new ToolbarLink('Block quote', 'quote', 'markdownToggleQuote'))
            ->setShortcut('Ctrl-Shift-Q'),
        (new ToolbarLink('Code', 'code', 'markdownToggleCodeBlock'))
            ->setShortcut('Ctrl-Shift-C'),
    ];
}
if (!$only || in_array('insert', $only)) {
    $buttons[] = [
        (new ToolbarLink('Link to any URL', 'link', null, new URL('&action=weblink')))
            ->setShortcut('Ctrl-Shift-K'),
        (new ToolbarLink('Link to a page', 'pages', null, new URL('&action=link')))
            ->setShortcut('Ctrl-K'),
        Permissions::inMetaGroup('richmedia__edit')
            ? (new ToolbarLink('Quick media insert', 'media', null, new URL('&action=media')))->setShortcut('Ctrl-M')
            : false,
        // (new ToolbarLink('Link to a user', 'person', null, new URL('&action=user')))
        //     ->setShortcut('Ctrl-U'),
    ];
}
if (!$only || in_array('advanced', $only)) {
    $buttons[] = [
        (new ToolbarLink('Toggle comment', 'hide', 'toggleComment', null))
            ->setShortcut('Ctrl-/ '),
    ];
}
if (!$only || in_array('history', $only)) {
    $buttons[] = [
        new ToolbarSpacer,
        new ToolbarSeparator,
        (new ToolbarLink('Undo', 'undo', 'undo'))
            ->setShortcut('Ctrl-Z'),
        (new ToolbarLink('Redo', 'redo', 'redo'))
            ->setShortcut('Ctrl-Y')
    ];
}
Dispatcher::dispatchEvent('onRichMediaToolbar', [&$buttons]);
if ($page) {
    Dispatcher::dispatchEvent('onRichMediaToolbar_' . $page->class(), [&$buttons]);
}
echo implode(
    new ToolbarSeparator,
    array_map(
        function ($bs) {
            return implode('', array_filter($bs));
        },
        $buttons
    )
);

echo '</div>';
