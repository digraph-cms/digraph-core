<h1>Authentication log</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Session;
use DigraphCMS\Spreadsheets\CellWriters\DateTimeCell;
use DigraphCMS\Spreadsheets\CellWriters\LongTextCell;
use DigraphCMS\Spreadsheets\CellWriters\UserCell;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Users;
use donatj\UserAgent\UserAgentParser;

$user = Users::get(Context::arg('user') ?? Session::user());
if (!$user) {
    throw new HttpError(404, "User not found");
}

$query = DB::query()
    ->from('session')
    ->select('created, comment, ip, ua, expires, session_expiration.date as date, session_expiration.reason as reason')
    ->leftJoin('session_expiration on session_expiration.session_id = session.id')
    ->where('session.user_uuid = ?', [$user->uuid()])
    ->order('session.created desc');

$parser = new UserAgentParser();
$table = new PaginatedTable(
    $query,
    function (array $row) use ($parser) {
        return [
            Format::date($row['created']),
            $row['comment'],
            $row['ip'],
            Session::fullBrowser($row['ua']) . '<br><small>' . $row['ua'] . '</small>',
            Format::date($row['expires']),
            @$row['date'] ? Format::date($row['date']) : "<em>N/A</em>",
            @$row['reason'] ?? "<em>N/A</em>"
        ];
    },
    [
        new ColumnSortingHeader('Date', 'session.created', $query),
        new ColumnHeader('Comment'),
        new ColumnHeader('IP'),
        new ColumnHeader('User agent'),
        new ColumnSortingHeader('Expiration', 'session.expires', $query),
        new ColumnSortingHeader('Deauthorized', 'session_expiration.date', $query),
        new ColumnHeader('Deauthorization reason'),
    ]
);
$table->paginator()->perPage(10);

$table->download(
    'authentication log',
    function (array $row) use ($user) {
        return [
            new UserCell($user),
            new DateTimeCell(Format::parseDate($row['created'])),
            new LongTextCell($row['comment']),
            new LongTextCell(Session::fullBrowser($row['ua']) . PHP_EOL . $row['ua']),
            $row['ip'],
            new DateTimeCell(Format::parseDate($row['expires'])),
            @$row['date'] ? new DateTimeCell(Format::date($row['date'])) : "",
            new LongTextCell(@$row['reason'] ?? ""),
        ];
    },
    [
        'User',
        'Date',
        'Comment',
        'User agent',
        'IP',
        'Expiration',
        'Deauthorized',
        'Deauthorization reason',
    ]
);

echo $table;
