<?php

use DigraphCMS\Context;
use DigraphCMS\Datastore\DatastoreItem;
use DigraphCMS\Datastore\DatastoreSelect;
use DigraphCMS\HTML\Icon;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$query = (new DatastoreSelect)
    ->order('updated DESC')
    ->where('ns', Context::url()->actionSuffix());

if (Context::arg('grp')) {
    $query->where('grp', Context::arg('grp'));
    echo "<h1>Group " . Context::arg('grp') . "</h1>";
    Breadcrumb::parent(new URL('namespace:' . Context::url()->actionSuffix()));
    Breadcrumb::setTopName('Group ' . Context::arg('grp'));
}

$table = new PaginatedTable(
    $query,
    function (DatastoreItem $item): array {
        return array_filter([
            Context::arg('grp') ? false : sprintf('<a href="%s">%s</a>', new URL('namespace:' . $item->namespaceName() . '?grp=' . $item->groupName()), $item->groupName()),
            sprintf('<a href="%s">%s</a>', new URL('item:' . $item->id()), $item->key()),
            htmlentities($item->value() ?? ' '),
            $item->data()->get() ? new Icon('code', 'contains data') : ' ',
            Format::date($item->created()),
            Format::date($item->updated())
        ]);
    },
    array_filter([
        Context::arg('grp') ? false : new ColumnStringFilteringHeader('Group', 'grp'),
        new ColumnStringFilteringHeader('Key', 'key'),
        new ColumnStringFilteringHeader('Value', 'value'),
        'Data',
        new ColumnDateFilteringHeader('Created', 'created'),
        new ColumnDateFilteringHeader('Updated', 'updated')
    ])
);

echo $table;
