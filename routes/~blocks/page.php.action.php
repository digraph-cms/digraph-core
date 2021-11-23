<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Blocks\Blocks;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\DataLists\BlockList;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\TabInterface;
use DigraphCMS\UI\Theme;
use DigraphCMS\URL\URL;

// ensure we have a UUID in the parameters
if (!Context::arg('page') || !Digraph::validateUUID(Context::arg('page') ?? '')) {
    throw new HttpError(400);
}

Context::response()->template('iframe.php');
Theme::addBlockingPageJs('/~blocks/editor-integration.js');

$tabs = new TabInterface();

$tabs->addTab(
    'list',
    'This page',
    function () use ($tabs) {
        $url = new URL('search_results.php');
        $url->query([
            'page' => Context::arg('page'),
            'editor' => Context::arg('editor')
        ]);
        echo "<iframe src='$url' class='embedded-iframe'></iframe>";
    }
);

$tabs->addTab(
    'manual',
    'Add block',
    function () {
        foreach (Config::get('block_types') as $type => $class) {
            $url = $class::url_add();
            $url->arg('editor', Context::arg('editor'));
            $url->arg('page', Context::arg('page'));
            echo sprintf(
                "<li><a href='%s'>%s</a></li>",
                $url,
                $class::className()
            );
        }
    }
);

$tabs->addTab(
    'external',
    'Other pages',
    function () {
        $url = new URL('/~blocks/search.php');
        $url->arg('editor', Context::arg('editor'));
        echo "<iframe src='$url' class='embedded-iframe'></iframe>";
    }
);

echo $tabs;
