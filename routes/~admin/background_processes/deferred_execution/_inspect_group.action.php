<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\Deferred;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnBooleanFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
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

echo '<div id="deferred-run-jobs" data-target="_frame" class="navigation-frame navigation-frame--stateless">';
if (Context::arg('run_jobs')) {
    echo new DeferredProgressBar($group);
} elseif ($count) {
    echo "<a href='" . new URL('&run_jobs=true') . "'>Run remaining jobs now</a>";
}
echo "</div>";

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
    ->order('CASE WHEN run is null THEN 1 ELSE 0 END')
    ->order('COALESCE(run,scheduled) asc, id asc');

if ($first && $last && $last['run']) {
    printf(
        '<p>Last job ran: %s</p>',
        Format::datetime($last['run'])
    );
}

echo new PaginatedTable(
    $jobs,
    function (array $row): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_job.html?id=' . $row['id']),
                $row['id']
            ),
            $row['scheduled'] ? Format::datetime($row['scheduled']) : '',
            $row['run'] ? Format::datetime($row['run']) : '<em>pending</em>',
            $row['message'],
            $row['error'] ? '<strong>yes</strong>' : '<em>none</em>'
        ];
    },
    [
        new ColumnSortingHeader('Job ID', 'id'),
        new ColumnDateFilteringHeader('Scheduled', 'scheduled'),
        new ColumnDateFilteringHeader('Run', 'run'),
        new ColumnStringFilteringHeader('Message', 'message'),
        new ColumnBooleanFilteringHeader('Error', 'error')
    ]
);
