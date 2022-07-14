<h1>Unsubscriptions</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;

echo new PaginatedTable(
    DB::query()->from('email_unsubscribe')
        ->order('time desc'),
    function (array $row): array {
        return [
            $row['email'],
            Emails::categoryLabel($row['category']),
            Format::datetime($row['time'])
        ];
    },
    [
        new ColumnHeader('Email address'),
        new ColumnHeader('Category'),
        new ColumnHeader('Date')
    ]
);
