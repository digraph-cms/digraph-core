<h1>Authentication log</h1>
<?php

use DigraphCMS\DB\DB;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use donatj\UserAgent\UserAgentParser;

$query = DB::query()
    ->from('sess_auth')
    ->select('created, comment, ip, ua, expires, sess_exp.date as date, sess_exp.reason as reason')
    ->leftJoin('sess_exp on sess_exp.auth = sess_auth.id')
    ->order('sess_auth.created desc');

$parser = new UserAgentParser();
$table = new QueryTable(
    $query,
    function (array $row) use ($parser) {
        $ua = $parser->parse($row['ua']);
        return [
            $row['created'],
            $row['comment'],
            $row['ip'],
            $ua->browser() . ' on ' . $ua->platform() . '<br><small>' . $row['ua'] . '</small>',
            $row['expires'],
            @$row['date'] ?? "<em>N/A</em>",
            @$row['reason'] ?? "<em>N/A</em>"
        ];
    },
    [
        new QueryColumnHeader('Date', 'sess_auth.created', $query),
        new ColumnHeader('Comment'),
        new ColumnHeader('IP'),
        new ColumnHeader('User agent'),
        new QueryColumnHeader('Expiration', 'sess_auth.expires', $query),
        new QueryColumnHeader('Deauthorized', 'sess_exp.date', $query),
        new ColumnHeader('Deauthorization reason'),
    ]
);
$table->paginator()->perPage(10);

echo $table;
