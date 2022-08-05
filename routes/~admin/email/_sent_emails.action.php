<h1>Sent email log</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

echo new PaginatedTable(
    Emails::select()
        ->where('error is null')
        ->order('time desc'),
    function (Email $email) {
        return [
            Format::datetime($email->time()),
            sprintf(
                "<a href='%s'>%s</a>",
                new URL('message/_message.html?uuid=' . $email->uuid()),
                $email->subject()
            ),
            $email->to()
        ];
    },
    [
        new ColumnDateFilteringHeader('Date', 'time'),
        new ColumnStringFilteringHeader('Subject', 'subject'),
        new ColumnStringFilteringHeader('To', '`to`'),
    ]
);
