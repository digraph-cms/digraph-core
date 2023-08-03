<h1>Deferred execution jobs</h1>

<p>
    Deferred execution jobs are used to allow slow operations to be executed in the background.
    They are also used to break large tasks into a series of smaller operations so that they can be run in a distributed
    way in the background while providing a progress bar.
</p>

<?php

use DigraphCMS\DB\DB;
use DigraphCMS\Spreadsheets\CellWriters\DateTimeCell;
use DigraphCMS\Spreadsheets\CellWriters\LinkCell;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$recent = DB::query()->from('defex')
    ->where('run is not null')
    ->where('error <> 1')
    ->order('run desc, id desc');

$upcoming = DB::query()->from('defex')
    ->where('run is null')
    ->order('CASE WHEN run is null THEN 1 ELSE 0 END')
    ->order('COALESCE(run,scheduled) asc, id asc');

$errors = DB::query()->from('defex')
    ->where('error = 1')
    ->order('run desc, id desc');

if ($errors->count()) {
    echo "<h2>Errors</h2>";
    $table = new PaginatedTable(
        $errors,
        function (array $row): array {
            return [
                sprintf(
                    '<a href="%s">%s</a>',
                    new URL('_inspect_job.html?id=' . $row['id']),
                    $row['id']
                ),
                sprintf(
                    '<a href="%s">%s</a>',
                    new URL('_inspect_group.html?id=' . $row['group']),
                    $row['group']
                ),
                $row['scheduled'] ? Format::datetime($row['scheduled']) : '',
                Format::datetime($row['run']),
                $row['message']
            ];
        },
        [
            'Job ID',
            'Group',
            new ColumnDateFilteringHeader('Scheduled', 'scheduled'),
            new ColumnDateFilteringHeader('Run', 'run'),
            new ColumnStringFilteringHeader('Message', 'message')
        ]
    );
    $table->download(
        'recent deferred execution errors',
        function (array $row) {
            return [
                new LinkCell($row['id'], new URL('_inspect_job.html?id=' . $row['id'])),
                new LinkCell($row['group'], new URL('_inspect_group.html?id=' . $row['group'])),
                $row['scheduled'] ? new DateTimeCell(Format::parseDate($row['scheduled'])) : '',
                new DateTimeCell(Format::parseDate($row['run'])),
                $row['message']
            ];
        },
        [
            'Job ID',
            'Group',
            new ColumnDateFilteringHeader('Scheduled', 'scheduled'),
            new ColumnDateFilteringHeader('Run', 'run'),
            new ColumnStringFilteringHeader('Message', 'message')
        ]
    );
    echo $table;
}

echo "<h2>Recently run</h2>";
$table = new PaginatedTable(
    $recent,
    function (array $row): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_job.html?id=' . $row['id']),
                $row['id']
            ),
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_group.html?id=' . $row['group']),
                $row['group']
            ),
            $row['scheduled'] ? Format::datetime($row['scheduled']) : '',
            Format::datetime($row['run']),
            $row['message']
        ];
    },
    [
        'Job ID',
        'Group',
        new ColumnDateFilteringHeader('Scheduled', 'scheduled'),
        new ColumnDateFilteringHeader('Run', 'run'),
        new ColumnStringFilteringHeader('Message', 'message')
    ]
);
$table->download(
    'recent deferred execution jobs',
    function (array $row) {
        return [
            new LinkCell($row['id'], new URL('_inspect_job.html?id=' . $row['id'])),
            new LinkCell($row['group'], new URL('_inspect_group.html?id=' . $row['group'])),
            $row['scheduled'] ? new DateTimeCell(Format::parseDate($row['scheduled'])) : '',
            new DateTimeCell(Format::parseDate($row['run'])),
            $row['message']
        ];
    },
    [
        'Job ID',
        'Group',
        'Scheduled',
        'Run',
        'Message'
    ]
);
echo $table;

echo "<h2>Upcoming runs</h2>";
$table = new PaginatedTable(
    $upcoming,
    function (array $row): array {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_job.html?id=' . $row['id']),
                $row['id']
            ),
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_group.html?id=' . $row['group']),
                $row['group']
            ),
            $row['scheduled'] ? Format::datetime($row['scheduled']) : '',
        ];
    },
    [
        'Job ID',
        'Group',
        new ColumnDateFilteringHeader('Scheduled', 'scheduled'),
    ]
);
$table->download(
    'upcoming deferred execution jobs',
    function (array $row) {
        return [
            new LinkCell($row['id'], new URL('_inspect_job.html?id=' . $row['id'])),
            new LinkCell($row['group'], new URL('_inspect_group.html?id=' . $row['group'])),
            $row['scheduled'] ? new DateTimeCell(Format::parseDate($row['scheduled'])) : '',
        ];
    },
    [
        'Job ID',
        'Group',
        'Scheduled'
    ]
);
echo $table;