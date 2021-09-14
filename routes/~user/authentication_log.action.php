<h1>Authentication log</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
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
$table = new QueryTable(
    $query,
    function (array $row) use ($parser) {
        $ua = $parser->parse($row['ua']);
        return [
            Format::date($row['created']),
            $row['comment'],
            $row['ip'],
            $ua->browser() . ' on ' . $ua->platform() . '<br><small>' . $row['ua'] . '</small>',
            Format::date($row['expires']),
            @$row['date'] ? Format::date($row['date']) : "<em>N/A</em>",
            @$row['reason'] ?? "<em>N/A</em>"
        ];
    },
    [
        new QueryColumnHeader('Date', 'session.created', $query),
        new ColumnHeader('Comment'),
        new ColumnHeader('IP'),
        new ColumnHeader('User agent'),
        new QueryColumnHeader('Expiration', 'session.expires', $query),
        new QueryColumnHeader('Deauthorized', 'session_expiration.date', $query),
        new ColumnHeader('Deauthorization reason'),
    ]
);
$table->paginator()->perPage(10);

echo $table;
