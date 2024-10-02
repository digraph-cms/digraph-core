<h1>Debug notes</h1>
<p>
    This page contains a bare-minimum implementation of Notes, under the namespace "debug" and all under a group also named "debug".
</p>
<?php

use DigraphCMS\Notes\Note;
use DigraphCMS\Notes\Notes;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$table = new PaginatedTable(
    Notes::namespace('debug')->select(),
    function (Note $note): array {
        return [
            sprintf('<a href="%s">%s</a>', new URL('note:' . $note->uuid()), $note->title()),
            sprintf('<a href="%s">%s</a>', $note->url_admin(), 'Datastore'),
            Format::datetime($note->created()),
            $note->createdBy(),
            Format::datetime($note->updated()),
            $note->updatedBy(),
        ];
    },
    [
        'Title',
        'Datastore view',
        'Created',
        'Created by',
        'Updated',
        'Updated by',
    ]
);

echo $table;
