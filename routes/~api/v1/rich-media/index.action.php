<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\DataTables\ArrayTable;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\TabInterface;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;

Permissions::requireMetaGroup('content__edit');

// fancy autocomplete search form
$acAdder = new AutocompleteInput('rich-media-type-search', new URL('/~api/v1/autocomplete/rich-media-type.php'));
$acAdder->setAttribute('placeholder', 'search to add media');
if (RichMedia::select(Context::arg('uuid'))->count()) {
    $acAdder->addClass('navigation-frame__autofocus');
}

// wrapper
$wrapper = (new DIV())
    ->setID(Context::arg('frame'));
$tabs = new TabInterface('tab');
$wrapper->addChild($tabs);

// adder script
$acAdder->setID($acAdderID = Digraph::uuid());
$url = new URL('&add={type}');
$wrapper->addChild(<<<EOL
<script>
    (() => {
        const ac = document.getElementById("$acAdderID");
        if (!ac) return;
        const url = "$url";
        ac.parentElement.addEventListener('autocomplete-select', (e) => {
            const evt = new Event('navigation-frame-navigate', {
                bubbles: true
            });
            evt.navigateUrl = url.replace('%7Btype%7D', e.autocompleteValue);
            ac.dispatchEvent(evt);
        });
    })();
</script>
EOL);

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
    $tabs->addTab('add2', 'Add ' . $class::className(), function () use ($class, $name) {
        echo "<div class='card card--light button-menu'>";
        $cancel = Context::url();
        $cancel->unsetArg('add');
        printf('<a href="%s" data-target="%s" class="button button--inverted button--warning">Cancel adding</a>', $cancel, Context::arg('frame'));
        echo "</div>";
        echo "<h2>Add " . $class::className() . "</h2>";
        Router::include($name . '/add.php');
    });
    $tabs->defaultTab('add2');
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
    $tabs->addTab('page', 'Existing media', function () use ($acAdder) {
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
        echo $acAdder;
        echo $table;
    });
}

// media adding tab
$tabs->addTab('add', 'Add media', function () use ($acAdder) {
    $table = new ArrayTable(
        Config::get('rich_media_types'),
        function ($name) {
            $class = Config::get('rich_media_types.' . $name);
            $url = Context::url();
            $url->arg('add', $name);
            $link = sprintf(
                '<a href="%s" data-target="%s" class="button button--inverted" style="white-space:nowrap;display:block;">%s</a>',
                $url,
                Context::arg('frame'),
                $class::className()
            );
            return [
                $link,
                '<small>' . $class::description() . '</small>'
            ];
        },
        []
    );
    echo $acAdder;
    echo $table;
});

echo $wrapper;
