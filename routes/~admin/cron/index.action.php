<h1>Cron jobs</h1>

<p>
    Cron jobs are scheduled tasks which run periodically in the background on your site.
    They are mostly used to perform routine maintenance tasks.
</p>

<?php

use DigraphCMS\DB\DB;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;

$recent = DB::query()->from('cron')
    ->where('run_last is not null')
    ->order('run_last desc');

$upcoming = DB::query()->from('cron')
    ->where('run_next >= ?', [time()])
    ->whereOr('(run_next < ? AND run_last < ?)', [time(), time()])
    ->order('run_next asc');

$errors = DB::query()->from('cron')
    ->where('error_time is not null')
    ->order('error_time desc');

if ($errors->count()) {
    echo "<h2>Errors</h2>";
    echo new QueryTable(
        $errors,
        function (array $row): array {
            return [
                (new ToolbarLink(
                    'Clear job (it may be automatically recreated)',
                    'delete',
                    function () use ($row) {
                        DB::query()->delete(
                            'cron',
                            $row['id']
                        )->execute();
                    }
                ))->setID('delete_' . $row['id']),
                $row['id'],
                Format::datetime($row['error_time']),
                $row['error_message'],
                $row['parent'],
                $row['name']
            ];
        },
        [
            new ColumnHeader(''),
            new ColumnHeader('Job ID'),
            new ColumnHeader('Time'),
            new ColumnHeader('Error message'),
            new ColumnHeader('Parent'),
            new ColumnHeader('Name')
        ]
    );
}

if ($recent->count()) {
    echo "<h2>Recently run</h2>";
    echo new QueryTable(
        $recent,
        function (array $row): array {
            return [
                $row['id'],
                Format::datetime($row['run_last']),
                $row['parent'],
                $row['name']
            ];
        },
        [
            new ColumnHeader('Job ID'),
            new ColumnHeader('Time'),
            new ColumnHeader('Parent'),
            new ColumnHeader('Name')
        ]
    );
}

if ($upcoming->count()) {
    echo "<h2>Upcoming runs</h2>";
    echo new QueryTable(
        $upcoming,
        function (array $row): array {
            return [
                $row['id'],
                Format::datetime($row['run_next']),
                $row['parent'],
                $row['name']
            ];
        },
        [
            new ColumnHeader('Job ID'),
            new ColumnHeader('Scheduled time'),
            new ColumnHeader('Parent'),
            new ColumnHeader('Name')
        ]
    );
}
