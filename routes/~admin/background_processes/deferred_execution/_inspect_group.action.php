<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

$group = Context::arg('id');
if (!$group || !Deferred::groupCount($group)) throw new HttpError(404, 'Group not found');

echo "<h1>Group $group</h1>";

printf(
    '<p>Total jobs: %s',
    Deferred::groupCount($group)
);

$pending = DB::query()->from('defex')
    ->where('`group` = ?', [$group])
    ->where('run is null')
    ->order('id asc');
if ($count = $pending->count()) {
    echo "<br>Pending jobs: $count</p>";
} else {
    echo "<br>All jobs complete</p>";
}

$first = DB::query()->from('defex')
    ->where('`group` = ?', [$group])
    ->order('id asc')
    ->limit(1)
    ->fetch();

$last = DB::query()->from('defex')
    ->where('`group` = ?', [$group])
    ->order('run desc')
    ->limit(1)
    ->fetch();

if ($first && $first['run']) {
    printf(
        '<p>First job ran: %s</p>',
        Format::datetime($first['run'])
    );
}

$jobs = DB::query()->from('defex')
    ->where('`group` = ?', [$group])
    ->order('id asc');

if ($first && $last && $last['run']) {
    printf(
        '<p>Last job ran: %s</p>',
        Format::datetime($last['run'])
    );
}

echo new QueryTable(
    $jobs,
    function (array $row): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_job.html?id=' . $row['id']),
                $row['id']
            ),
            $row['run'] ? Format::datetime($row['run']) : '<em>pending</em>',
            $row['message'],
            $row['error'] ? '<strong>yes</strong>' : '<em>none</em>'
        ];
    },
    [
        new ColumnHeader('Job ID'),
        new ColumnHeader('Time'),
        new ColumnHeader('Message'),
        new ColumnHeader('Error')
    ]
);
