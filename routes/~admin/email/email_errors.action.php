<h1>Email error log</h1>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$emails = Emails::select()
    ->where('error is not null')
    ->order('time desc');

if ($emails->count()) {
    Notifications::printNoticeHTML(sprintf(
        '<a href="%s">Use the requeue tool to attempt resending emails</a>',
        new URL('_requeue_errors.html'),
    ));
}

echo new PaginatedTable(
    $emails,
    function (Email $email) {
        return [
            Format::datetime($email->time()),
            $email->error(),
            sprintf(
                "<a href='%s'>%s</a>",
                $email->url_adminInfo(),
                $email->subject(),
            ),
            $email->to(),
        ];
    },
    [
        new ColumnDateFilteringHeader('Date', 'time'),
        new ColumnStringFilteringHeader('Error', 'error'),
        new ColumnStringFilteringHeader('Subject', 'subject'),
        new ColumnStringFilteringHeader('To', '`to`'),
    ],
);
