<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Forms\PageField;
use DigraphCMS\UI\Notifications;
use Formward\Fields\Input;

echo '<div class="navigation-frame" id="children-form">';

// set up and handle form first, so that its changes appear in table immediately
$form = new Form('Add child');
$form->addClass('compact');
$form['child'] = new PageField('Page');
$form['child']->required(true);
$form['type'] = new Input('Link type');

if ($form->handle()) {
    Pages::insertLink(
        Context::page()->uuid(),
        $form['child']->value(),
        $form['type']->value() ? $form['type']->value() : null
    );
    Notifications::confirmation('Link added');
    $form['child']->value('');
}

// display table
$query = Graph::childIDs(Context::page()->uuid())->order('page_link.id desc');
$table = new QueryTable(
    $query,
    function (array $row) {
        $page = Pages::get($row['end_page']);
        $button = new SingleButton(
            'Remove',
            function () use ($row) {
                DB::query()
                    ->delete('page_link')
                    ->where('id = ?', [$row['id']])
                    ->execute();
            },
            ['warning']
        );
        return [
            $page ? $page->url()->html() : $page,
            $row['type'],
            $button
        ];
    },
    [
        new ColumnHeader('Child'),
        new QueryColumnHeader('Type', 'page_link.type', $query),
        new ColumnHeader('Remove link')
    ]
);
echo $table;

// display form below table
echo $form;

echo '</div>';
