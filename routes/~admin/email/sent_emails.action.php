<h1>Sent email log</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;

echo new PaginatedTable(
    Emails::select()
        ->sent()
        ->notErrored()
        ->order('id DESC'),
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
