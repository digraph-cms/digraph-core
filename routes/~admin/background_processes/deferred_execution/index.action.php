<h1>Deferred execution jobs</h1>

<p>
    Deferred execution jobs are used to allow slow operations to be executed in the background.
    They are also used to break large tasks into a series of smaller operations so that they can be run in a distributed way in the background while providing a progress bar.
</p>

<?php

use DigraphCMS\DB\DB;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;

$recent = DB::query()->from('defex')
    ->where('run is not null')
    ->where('error = 0')
    ->order('run desc, id desc');

$upcoming = DB::query()->from('defex')
    ->where('run is null')
    ->order('id asc');

$errors = DB::query()->from('defex')
    ->where('error = 1')
    ->order('run desc, id desc');

if ($errors->count()) {
    echo "<h2>Errors</h2>";
    echo new QueryTable(
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
                Format::datetime($row['run']),
                $row['message']
            ];
        },
        [
            new ColumnHeader('Job ID'),
            new ColumnHeader('Group'),
            new ColumnHeader('Time'),
            new ColumnHeader('Message')
        ]
    );
}

echo "<h2>Recently run</h2>";
echo new QueryTable(
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
            Format::datetime($row['run']),
            $row['message']
        ];
    },
    [
        new ColumnHeader('Job ID'),
        new ColumnHeader('Group'),
        new ColumnHeader('Time'),
        new ColumnHeader('Message')
    ]
);

echo "<h2>Upcoming runs</h2>";
echo new QueryTable(
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
            )
        ];
    },
    [
        new ColumnHeader('Job ID'),
        new ColumnHeader('Group')
    ]
);
