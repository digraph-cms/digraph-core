<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\TabInterface;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

Permissions::requireMetaGroup('content__edit');
Context::response()->template('iframe.php');

$wrapper = (new DIV())
    ->setID(Context::arg('frame'));
$tabs = new TabInterface('tab');
$wrapper->addChild($tabs);

// delete tab takes over completely as required
if (Context::arg('delete') && $media = RichMedia::get(Context::arg('delete'))) {
    $tabs->addTab('delete', 'Delete', function () use ($media) {
        printf(
            '<p>Are you sure you want to delete the rich media content "%s"? This action cannot be undone.</p>',
            $media->name()
        );
        $url = Context::url();
        $url->unsetArg('delete');
        $menu = new ButtonMenu();
        $menu->addButton(new ButtonMenuButton(
            'Yes, permanently delete',
            function () use ($url, $media) {
                $media->delete();
                throw new RedirectException($url);
            },
            ['button--error']
        ));
        $menu->addButton(new ButtonMenuButton(
            'No, cancel',
            function () use ($url, $media) {
                throw new RedirectException($url);
            }
        ));
        echo $menu;
    });
    $tabs->defaultTab('delete');
    echo $wrapper;
    return;
}

// adding tab takes over completely as required
if (($name = Context::arg('add')) && $class = Config::get("rich_media_types.$name")) {
    $tabs->addTab('add', 'Add ' . $class::className(), function () use ($class, $name) {
        echo "<div class='card card--light button-menu'>";
        $cancel = Context::url();
        $cancel->unsetArg('add');
        printf('<a href="%s" data-target="%s" class="button button--inverted button--warning">Cancel adding</a>', $cancel, Context::arg('frame'));
        echo "</div>";
        Router::include($name . '/add.php');
    });
    $tabs->defaultTab('add');
    echo $wrapper;
    return;
}

// editing tab takes over completely as required
if (Context::arg('edit') && $media = RichMedia::get(Context::arg('edit'))) {
    $tabs->addTab('edit', 'Edit', function () use ($media) {
        echo "<div class='card card--light button-menu'>";
        $cancel = Context::url();
        $cancel->unsetArg('edit');
        printf('<a href="%s" data-target="%s" class="button button--inverted button--warning">Cancel editing</a>', $cancel, Context::arg('frame'));
        $cancel = Context::url();
        $cancel->arg('delete', $cancel->arg('edit'));
        printf('<a href="%s" data-target="%s" class="button button--inverted button--error">Delete media</a>', $cancel, Context::arg('frame'));
        echo "</div>";
        Router::include($media->class() . '/edit.php');
    });
    $tabs->defaultTab('edit');
    echo $wrapper;
    return;
}

// page media list
if (Context::arg('uuid') && RichMedia::select(Context::arg('uuid'))->count()) {
    $tabs->addTab('page', 'Page media', function () {
        $query = RichMedia::select(Context::arg('uuid'))
            ->order('updated DESC');
        $table = new QueryTable(
            $query,
            function (AbstractRichMedia $media) {
                $name = $media->name();
                $url = new URL('&edit=' . $media->uuid());
                $name = sprintf(
                    '<a href="%s" data-target="%s" class="button button--inverted" style="display:block">%s</a>',
                    $url,
                    Context::arg('frame'),
                    $name
                );
                return [
                    $name . PHP_EOL . $media->insertInterface(),
                    Format::date($media->updated())
                ];
            },
            [
                new QueryColumnHeader('Media', 'name', $query),
                new QueryColumnHeader('Modified', 'updated', $query)
            ]
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

// global/search tab
$tabs->addTab('search', 'Search', function () {
    echo "<div class='navigation-frame navigation-frame--stateless' id='search_" . Context::arg('frame') . "'>";
    // set up query
    $query = RichMedia::select();
    $query->order('updated desc');
    if ($q = Context::arg('query')) {
        foreach (explode(' ', $q) as $word) {
            $word = strtolower(trim($word));
            if ($word) {
                $query->whereOr('name like ?', "%$word%");
                $query->whereOr('class = ?', $word);
                $query->whereOr('data like ?', "%$word%");
            }
        }
    }
    // set up search form, note that it bounces to put the query into a GET arg
    // this is necessary to have pagination work
    $form = new FormWrapper();
    $search = new INPUT();
    $search->setDefault(Context::arg('query'));
    $form->addChild($search);
    $form->setStyle('display', 'flex');
    $form->button()->setText('Search');
    $form->addCallback(function () use ($query, $search) {
        $url = Context::url();
        $url->arg('query',$search->value());
        throw new RedirectException($url);
    });
    echo $form;
    // table for displaying results
    $table = new QueryTable(
        $query,
        function (AbstractRichMedia $media) {
            $page = $media->pageUUID() ? Pages::get($media->pageUUID()) : null;
            $name = $media->name();
            $url = new URL('&edit=' . $media->uuid());
            $name = sprintf(
                '<a href="%s" data-target="%s" class="button button--inverted" style="display:block">%s</a>',
                $url,
                Context::arg('frame'),
                $name
            );
            return [
                $name . PHP_EOL . $media->insertInterface(),
                $page ? $page->url()->html() : '<em>N/A</em>',
                Format::date($media->updated())
            ];
        },
        [
            new QueryColumnHeader('Media', 'name', $query),
            new ColumnHeader('Page'),
            new QueryColumnHeader('Modified', 'updated', $query)
        ]
    );
    echo $table;
    echo "</div>";
});

echo $wrapper;
