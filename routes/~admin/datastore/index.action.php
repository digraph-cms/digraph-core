<h1>Datastore browser</h1>
<p>
    The Datastore database can be used by any subsystem to easily read and write key/value data.
    All Datastore data is accessible from anywhere, and it's generally intended for smaller key/value stores to be maintained without having to build whole new database tables.
</p>
<?php

use DigraphCMS\Datastore\DatastoreItem;
use DigraphCMS\Datastore\DatastoreSelect;
use DigraphCMS\HTML\Icon;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$datastore = (new DatastoreSelect)
    ->order('id DESC');

$table = new PaginatedTable(
    $datastore,
    function (DatastoreItem $item): array {
        return [
            sprintf('<a href="%s">%s</a>', new URL('namespace:' . $item->namespaceName()), $item->namespaceName()),
            sprintf('<a href="%s">%s</a>', new URL('namespace:' . $item->namespaceName() . '?grp=' . $item->groupName()), $item->groupName()),
            sprintf('<a href="%s">%s</a>', new URL('item:' . $item->id()), $item->key()),
            htmlentities($item->value() ?? ''),
            $item->data()->get() ? new Icon('code', 'contains data') : '',
            Format::date($item->created()),
            Format::date($item->updated())
        ];
    },
    [
        new ColumnStringFilteringHeader('Namespace', 'ns'),
        new ColumnStringFilteringHeader('Group', 'grp'),
        new ColumnStringFilteringHeader('Key', 'key'),
        new ColumnStringFilteringHeader('Value', 'value'),
        'Data',
        new ColumnDateFilteringHeader('Created', 'created'),
        new ColumnDateFilteringHeader('Updated', 'updated')
    ]
);

echo $table;
