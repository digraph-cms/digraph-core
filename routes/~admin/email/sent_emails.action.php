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
        ->where('sent is not null')
        ->order('sent desc'),
    function (Email $email) {
        return [
            Format::datetime($email->time()),
            sprintf(
                "<a href='%s'>%s</a>",
                $email->url_adminInfo(),
                $email->subject()
            ),
            $email->to()
        ];
    },
    [
        new ColumnDateFilteringHeader('Sent', 'sent'),
        new ColumnStringFilteringHeader('Subject', 'subject'),
        new ColumnStringFilteringHeader('To', '`to`'),
    ]
);
