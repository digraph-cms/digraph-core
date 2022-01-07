<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\TabInterface;

Context::response()->template('iframe.php');

$wrapper = (new DIV())
    ->setID(Context::arg('frame'));
$tabs = new TabInterface('tab');
$wrapper->addChild($tabs);

// editing tab takes over completely as required
if (Context::arg('edit') && $media = RichMedia::get(Context::arg('edit'))) {
    $tabs->addTab('edit', 'Edit', function () use ($media) {
        $cancel = Context::url();
        $cancel->unsetArg('edit');
        printf('<a href="%s" data-target="%s" class="button button--warning">cancel</a>', $cancel, Context::arg('frame'));
        Router::include($media->class() . '/edit.php');
    });
    $tabs->defaultTab('edit');
    echo $wrapper;
    return;
}

// page media list
if (RichMedia::select(Context::arg('page'))->count()) {
    $tabs->addTab('page', 'Page media', function () {
        $query = RichMedia::select(Context::arg('page'));
        $table = new QueryTable(
            $query,
            function (AbstractRichMedia $media) {
                $edit = Context::url();
                $edit->arg('edit', $media->uuid());
                return [
                    sprintf('<a href="%s" data-target="%s" class="button button--info">edit</a>', $edit, Context::arg('frame'))
                ];
            },
            []
        );
        echo $table;
    });
}

// media adding tab
$tabs->addTab('add', 'Add', function () {
    echo "<p>TODO: add media form</p>";
});

$tabs->addTab('search', 'Search', function () {
    echo "<p>TODO: list/search media globally</p>";
});

echo $wrapper;
