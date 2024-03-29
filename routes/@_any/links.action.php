<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\PageField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\ColumnPageFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\TabInterface;

$tabs = new TabInterface();

$fn = function () use ($tabs) {
    echo '<div class="navigation-frame" id="network-form">'; // open navigation frame

    $mode = $tabs->activeTab();

    // display table of connections
    if ($mode == 'children') {
        $query = Graph::childEdges(Context::page()->uuid())->order('page_link.id desc');
    } else {
        $query = Graph::parentEdges(Context::page()->uuid())->order('page_link.id desc');
    }
    $table = new PaginatedTable(
        $query,
        function (array $row) use ($mode) {
            $page = ($mode == 'children' ? Pages::get($row['end_page']) : Pages::get($row['start_page']));
            $button = (new CallbackLink(
                function () use ($row) {
                    DB::query()
                        ->delete('page_link')
                        ->where('id = ?', [$row['id']])
                        ->execute();
                }
            ))
                ->addChild('Remove')
                ->addClass('button button--warning');
            return [
                $page ? $page->url()->html() : $page,
                $row['type'],
                $button
            ];
        },
        [
            new ColumnPageFilteringHeader(
                $mode == 'children' ? 'Child' : 'Parent',
                $mode == 'children' ? 'link_end' : 'link_start'
            ),
            new ColumnStringFilteringHeader('Type', 'page_link.type'),
            new ColumnHeader('Remove link')
        ]
    );
    $table->paginator()->perPage(10);
    echo $table;

    // display form for adding connections
    $target = (new PageField('Add new ' . ($mode == 'children' ? 'child' : 'parent')))
        ->setRequired(true);
    $type = (new Field('Edge type'))
        ->setDefault('normal')
        ->setRequired(true);
    $form = new FormWrapper(Context::pageUUID() . '_' . $mode);
    $form->addChild($target);
    $form->addChild($type);
    $form->addCallback(function () use ($mode, $target, $type) {
        try {
            if ($mode == 'children') {
                Pages::insertLink(Context::pageUUID(), $target->value(), $type->value());
            } else {
                Pages::insertLink($target->value(), Context::pageUUID(), $type->value());
            }
        } catch (\Throwable $th) {
            if ($th instanceof Exception) {
                Notifications::flashError($th->getMessage());
            } else {
                Notifications::flashError(get_class($th));
            }
        }
        throw new RefreshException();
    });
    echo $form;

    echo '</div>'; // close navigation frame
};

$tabs->addTab('parents', 'Parents', $fn);
$tabs->addTab('children', 'Children', $fn);
$tabs->defaultTab('parents');
echo $tabs;
