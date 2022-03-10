<h1>Email error log</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

echo new QueryTable(
    Emails::select()
        ->where('error is not null')
        ->order('time desc'),
    function (Email $email) {
        return [
            Format::datetime($email->time()),
            $email->error(),
            sprintf(
                "<a href='%s'>%s</a>",
                new URL('message/_message.html?uuid=' . $email->uuid()),
                $email->subject()
            ),
            $email->to()
        ];
    },
    [
        new ColumnHeader('Date'),
        new ColumnHeader('Error'),
        new ColumnHeader('Subject'),
        new ColumnHeader('To')
    ]
);
