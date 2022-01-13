<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\TabInterface;

Context::response()->template('iframe.php');

$wrapper = (new DIV())
    ->setID(Context::arg('frame'));
$tabs = new TabInterface('tab');
$wrapper->addChild($tabs);

// adding tab takes over completely as required
if (($name = Context::arg('add')) && $class = Config::get("rich_media_types.$name")) {
    $tabs->addTab('add', 'Add ' . $class::className(), function () use ($class,$name) {
        $cancel = Context::url();
        $cancel->unsetArg('add');
        printf('<a href="%s" data-target="%s" class="button button--warning">Cancel adding</a>', $cancel, Context::arg('frame'));
        echo "<hr>";
        Router::include($name . '/add.php');
    });
    $tabs->defaultTab('add');
    echo $wrapper;
    return;
}

// editing tab takes over completely as required
if (Context::arg('edit') && $media = RichMedia::get(Context::arg('edit'))) {
    $tabs->addTab('edit', 'Edit', function () use ($media) {
        echo "<div class='button-menu'>";
        $cancel = Context::url();
        $cancel->unsetArg('edit');
        printf('<a href="%s" data-target="%s" class="button button--warning">Cancel editing</a>', $cancel, Context::arg('frame'));
        $cancel = Context::url();
        $cancel->arg('delete', $cancel->arg('edit'));
        $cancel->unsetArg('edit');
        printf('<a href="%s" data-target="%s" class="button button--error">Delete media</a>', $cancel, Context::arg('frame'));
        echo "</div>";
        echo "<hr>";
        Router::include($media->class() . '/edit.php');
    });
    $tabs->defaultTab('edit');
    echo $wrapper;
    return;
}

// page media list
if (RichMedia::select(Context::arg('page'))->count()) {
    $tabs->addTab('page', 'Page media', function () {
        $query = RichMedia::select(Context::arg('page'))
            ->order('updated DESC');
        $table = new QueryTable(
            $query,
            function (AbstractRichMedia $media) {
                $edit = Context::url();
                $edit->arg('edit', $media->uuid());
                return [
                    $media->name(),
                    sprintf('<a href="%s" data-target="%s" class="button button--info">edit</a>', $edit, Context::arg('frame'))
                ];
            },
            []
        );
        $table->paginator()->perPage(10);
        echo $table;
    });
}

// media adding tab
$tabs->addTab('add', 'Add media', function () {
    $table = new ArrayTable(
        Config::get('rich_media_types'),
        function ($name) {
            $class = Config::get('rich_media_types.' . $name);
            $url = Context::url();
            $url->arg('add', $name);
            $link = sprintf(
                '<a href="%s" data-target="%s">%s</a>',
                $url,
                Context::arg('frame'),
                $class::className()
            );
            return [$link];
        },
        []
    );
    echo $table;
});

$tabs->addTab('search', 'Search', function () {
    echo "<p>TODO: list/search media globally</p>";
});

echo $wrapper;
