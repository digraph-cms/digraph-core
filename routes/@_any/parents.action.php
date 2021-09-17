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

echo '<div class="navigation-frame" id="parents-form">';

$query = Graph::parentIDs(Context::page()->uuid())->order('page_link.id desc');
$table = new QueryTable(
    $query,
    function (array $row) {
        $page = Pages::get($row['start_page']);
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
            $page->url()->html(),
            $row['type'],
            $button
        ];
    },
    [
        new ColumnHeader('Parent'),
        new QueryColumnHeader('Type', 'page_link.type', $query),
        new ColumnHeader('Remove link')
    ]
);
echo $table;

$form = new Form('Add parent');
$form->addClass('compact');
$form['parent'] = new PageField('Page');
$form['parent']->required(true);
$form['type'] = new Input('Link type');

if ($form->handle()) {
    Pages::insertLink(
        $form['parent']->value()->uuid(),
        Context::page()->uuid(),
        $form['type']->value() ? $form['type']->value() : null
    );
    Notifications::flashConfirmation('Link added');
    throw new RefreshException();
} else {
    echo $form;
}

echo '</div>';
