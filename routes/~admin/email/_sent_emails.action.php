<h1>Sent email log</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

echo new QueryTable(
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
        new ColumnHeader('Date'),
        new ColumnHeader('Subject'),
        new ColumnHeader('To')
    ]
);