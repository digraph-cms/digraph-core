<h1>Manage all rich media</h1>
<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\RichMedia\Types\AbstractRichMedia;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnUserFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;

echo '<div id="global_manager" data-initial class="navigation-frame navigation-frame--stateless">';

// fancy autocomplete search form
$form = (new FormWrapper())
    ->addClass('autoform')
    ->setID('global_manager_adder');
$form->button()->setText('Add');
$adder = (new AutocompleteInput('rich-media-type-search', new URL('/api/v1/autocomplete/rich-media-type.php')))
    ->addClass('autocomplete-input--autopopulate')
    ->addClass('autocomplete-input--autosubmit')
    ->setAttribute('placeholder', 'create rich media')
    ->setID('adder');
$form->addChild($adder);
echo $form;

// script to launch editor if a new item is created
if ($form->ready()) {
    printf(
        '<script>Digraph.popup("%s","%s");</script>',
        new URL(sprintf(
            '/richmedia/editor/?add=%s&frame=%s&parent=%s',
            $adder->value(),
            'global_manager',
            'global'
        )),
        Digraph::uuid()
    );
}

// display list
$media = RichMedia::select()
    ->order('updated DESC');
$table = new PaginatedTable(
    $media,
    function (AbstractRichMedia $media): array {
        $main = (new DIV)
            ->addChild('<strong>' . $media->icon() . ' ' . $media->name() . '</strong>')
            ->setAttribute('draggable', 'true')
            ->setData('drag-content', $media->defaultTag());
        $parent = $media->parent();
        if (Pages::get($parent)) $parent = Pages::get($parent)->url()->html();
        return [
            (new ToolbarLink('Edit', 'edit', null, null))
                ->addClass('toolbar__button--compact-tip')
                ->setAttribute('onclick', sprintf(
                    'Digraph.popup("%s")',
                    new URL('/richmedia/editor/?frame=global_manager&uuid=' . $media->uuid())
                )),
            $main,
            $parent,
            Format::date($media->created()),
            $media->createdBy(),
            Format::date($media->updated()),
            $media->updatedBy(),
        ];
    },
    [
        '',
        new ColumnStringFilteringHeader('Name', 'name'),
        'Parent',
        new ColumnDateFilteringHeader('Created', 'created'),
        new ColumnUserFilteringHeader('By', 'created_by'),
        new ColumnDateFilteringHeader('Updated', 'updated'),
        new ColumnUserFilteringHeader('By', 'updated_by'),
    ]
);
$table->addClass('rich-content-editor__media-list');
$table->paginator()->perPage(10);
echo $table;

echo '</div>';
