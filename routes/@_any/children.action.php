<?php

use DigraphCMS\Content\Graph;
use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\UI\Forms\PageField;
use DigraphCMS\UI\Notifications;
use Formward\Fields\Input;

echo '<div class="navigation-frame" id="children-form">';

$query = Graph::childIDs(Context::page()->uuid())->order('page_link.id desc');
$table = new QueryTable(
    $query,
    function (array $row) {
        $page = Pages::get($row['end_page']);
        $button = 'TODO: delete button';
        return [
            $page->url()->html(),
            $row['type'],
            $button
        ];
    },
    [
        new ColumnHeader('Child'),
        new QueryColumnHeader('Type', 'page_link.type', $query),
        new ColumnHeader('Delete')
    ]
);
echo $table;

$form = new Form('Add child');
$form->addClass('compact');
$form['child'] = new PageField('Page');
$form['child']->required(true);
$form['type'] = new Input('Link type');

if ($form->handle()) {
    Pages::insertLink(
        Context::page()->uuid(),
        $form['child']->value()->uuid(),
        $form['type']->value() ? $form['type']->value() : null
    );
    Notifications::flashConfirmation('Link added');
    throw new RefreshException();
} else {
    echo $form;
}

echo '</div>';
