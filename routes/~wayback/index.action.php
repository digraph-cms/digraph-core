<h1>Wayback dashboard</h1>
<?php

use DigraphCMS\Datastore\DatastoreItem;
use DigraphCMS\Datastore\DatastoreNamespace;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

echo new PaginatedTable(
    (new DatastoreNamespace('wayback'))->select()->order('updated DESC'),
    function (DatastoreItem $item): array {
        $data = $item->data();
        $action = $item->group()->name();
        $url = '';
        switch ($item->group()->name()) {
            case 'status':
                $action = 'Link status: ' . $item->value();
                $url = $data['url'];
                break;
            case 'api':
                $action = 'API call: ' . $item->value();
                $url = $data['url'];
                break;
            case 'page':
                $action = sprintf('<a href="%s">Landing page generated</a>', new URL('page:' . $item->key()));
                $url = $data['original_url'];
        }
        return [
            Format::date($item->updated()),
            $action,
            htmlspecialchars($url)
        ];
    },
    []
);
