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
    $buttons['format'] = [
        'heading' => (new ToolbarLink('Heading', 'heading', 'markdownToggleHeading'))
            ->setShortcut('Ctrl-H'),
        'heading_reverse' => (new ToolbarLink('Heading (reversed)', 'heading', 'markdownToggleHeadingReverse'))
            ->setShortcut('Ctrl-Shift-H')
            ->setStyle('display', 'none'),
        'bold' => (new ToolbarLink('Bold', 'bold', 'markdownToggleBold'))
            ->setShortcut('Ctrl-B'),
        'italic' => (new ToolbarLink('Italic', 'italic', 'markdownToggleItalic'))
            ->setShortcut('Ctrl-I'),
        'strike' => (new ToolbarLink('Strike', 'strikethrough', 'markdownToggleStrikethrough'))
            ->setShortcut('Ctrl-Shift-S'),
        'highlight' => (new ToolbarLink('Highlight', 'highlight', 'markdownToggleHighlight'))
            ->setShortcut('Ctrl-Shift-M'),
    ];
}
if (!$only || in_array('blocks', $only)) {
    $buttons['blocks'] = [
        'bullet_list' => (new ToolbarLink('Bullet list', 'list-bullet', 'markdownToggleBulletList'))
            ->setShortcut('Ctrl-L'),
        'numbered_list' => (new ToolbarLink('Numbered list', 'list-numbered', 'markdownToggleNumberedList'))
            ->setShortcut('Ctrl-Shift-L'),
        'blockquote' => (new ToolbarLink('Block quote', 'quote', 'markdownToggleQuote'))
            ->setShortcut('Ctrl-Shift-Q'),
        'code' => (new ToolbarLink('Code', 'code', 'markdownToggleCodeBlock'))
            ->setShortcut('Ctrl-Shift-C'),
    ];
}
if (!$only || in_array('insert', $only)) {
    $buttons['insert'] = [
        'weblink' => (new ToolbarLink('Link to any URL', 'link', null, new URL('&action=weblink')))
            ->setShortcut('Ctrl-Shift-K'),
        'link' => (new ToolbarLink('Link to a page', 'pages', null, new URL('&action=link')))
            ->setShortcut('Ctrl-K'),
        'richmedia' => Permissions::inMetaGroup('richmedia__edit')
            ? (new ToolbarLink('Quick media insert', 'media', null, new URL('&action=media')))->setShortcut('Ctrl-M')
            : false,
    ];
}
if (!$only || in_array('advanced', $only)) {
    $buttons['advanced'] = [
        'comment' => (new ToolbarLink('Toggle comment', 'hide', 'toggleComment', null))
            ->setShortcut('Ctrl-/ '),
    ];
}
if (!$only || in_array('history', $only)) {
    $buttons['history'] = [
        'spacer' => new ToolbarSpacer,
        'separator' => new ToolbarSeparator,
        'undo' => (new ToolbarLink('Undo', 'undo', 'undo'))
            ->setShortcut('Ctrl-Z'),
        'redo' => (new ToolbarLink('Redo', 'redo', 'redo'))
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
