<h1>Cron jobs</h1>

<p>
    Cron jobs are scheduled tasks which run periodically in the background on your site.
    They are mostly used to perform routine maintenance tasks.
</p>

<?php

use DigraphCMS\DB\DB;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\URL\URL;

$recent = DB::query()->from('cron')
    ->where('run_last is not null')
    ->order('run_last desc');

$upcoming = DB::query()->from('cron')
    ->where('(run_last < ? OR run_last IS NULL)', [time()])
    ->order('run_next asc');

$errors = DB::query()->from('cron')
    ->where('error_time is not null')
    ->order('error_time desc');

if ($errors->count()) {
    echo "<h2>Errors</h2>";
    echo new PaginatedTable(
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
                sprintf(
                    '<a href="%s">%s</a>',
                    new URL('_inspect_job.html?id=' . $row['id']),
                    $row['name']
                )
            ];
        },
        [
            '',
            'Job ID',
            new ColumnDateFilteringHeader('Time', 'error_time'),
            new ColumnStringFilteringHeader('Error message', 'error_message'),
            new ColumnStringFilteringHeader('Parent', 'parent'),
            new ColumnStringFilteringHeader('Name', 'name'),
        ]
    );
}

echo "<h2>Recently run</h2>";
echo new PaginatedTable(
    $recent,
    function (array $row): array {
        return [
            $row['id'],
            Format::datetime($row['run_last']),
            $row['parent'],
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_job.html?id=' . $row['id']),
                $row['name']
            )
        ];
    },
    [
        'Job ID',
        new ColumnDateFilteringHeader('Time', 'run_last'),
        new ColumnStringFilteringHeader('Parent', 'parent'),
        new ColumnStringFilteringHeader('Name', 'name'),
    ]
);

echo "<h2>Upcoming runs</h2>";
echo new PaginatedTable(
    $upcoming,
    function (array $row): array {
        return [
            $row['id'],
            Format::datetime($row['run_next']),
            $row['parent'],
            sprintf(
                '<a href="%s">%s</a>',
                new URL('_inspect_job.html?id=' . $row['id']),
                $row['name']
            )
        ];
    },
    [
        'Job ID',
        new ColumnDateFilteringHeader('Scheduled time', 'run_next'),
        new ColumnStringFilteringHeader('Parent', 'parent'),
        new ColumnStringFilteringHeader('Name', 'name'),
    ]
);
