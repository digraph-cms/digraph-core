<h1>Unsubscriptions</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;

echo new QueryTable(
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
