<h1>Unsubscriptions</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
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
        new ColumnStringFilteringHeader('Email address', 'email'),
        'Category',
        new ColumnDateFilteringHeader('Date', 'time')
    ]
);
