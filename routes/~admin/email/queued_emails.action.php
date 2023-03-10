<h1>Queued emails</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

echo new PaginatedTable(
    Emails::select()->queue(),
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
        new ColumnDateFilteringHeader('Queued', 'time'),
        new ColumnStringFilteringHeader('Subject', 'subject'),
        new ColumnStringFilteringHeader('To', '`to`'),
    ]
);
