<h1>Clean up filestore</h1>
<p>
    This tool allows you to clean up unused files from the filestore system.
    Generally these are the result of abandoned page-creation sessions, so to avoid deleting work in progress by users, this tool will only display or delete abandoned files older than 24 hours.
</p>
<p>
    Filestore files are deduplicated, so storing an identical file multiple times does not use additional disk space.
    This deduplication also means that deleting a file here will only free up disk space if there is not an identical file stored elsewhere.
</p>
<?php

use DigraphCMS\Content\Filestore;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;

// prepare a query and exit if its count is zero
$query = DB::query()->from('filestore')
    ->leftJoin('page ON page_uuid = page.uuid')
    ->where('page_uuid is not null')
    ->where('page.uuid is null')
    // ->where('filestore.created < ?', [time() - 86400])
    ->order('filestore.created ASC');

if ($query->count()) {
    // count total size of unlinked files and display notice of count and size
    $byteQuery = DB::query()->from('filestore')
        ->select('sum(filestore.bytes) as totalBytes')
        ->leftJoin('page ON page_uuid = page.uuid')
        ->where('page_uuid is not null')
        ->where('page.uuid is null')
        ->where('filestore.created < ?', [time() - 86400]);
    Notifications::notice(sprintf(
        "There are currently %d files linked to nonexistent pages with a total potential filesize of %s",
        $query->count(),
        Format::filesize($byteQuery->fetch()['totalBytes'] ?? 0)
    ));
}

// display files in table
$table = new QueryTable(
    $query,
    function ($row): array {
        $file = Filestore::get($row['uuid']);
        return [
            sprintf('<a href="%s">%s</a>', $file->url(), $row['filename']),
            Format::filesize($row['bytes']),
            Format::date($row['created']),
            Users::user($row['created_by']),
            new SingleButton(
                'Delete',
                function () use ($file) {
                    if ($file->delete()) {
                        Notifications::flashConfirmation('Deleted file ' . $file->filename());
                    } else {
                        Notifications::flashError('Error deleting file ' . $file->filename());
                    }
                    throw new RefreshException();
                },
                ['error']
            )
        ];
    },
    [
        new QueryColumnHeader('File', 'filestore.filename', $query),
        new QueryColumnHeader('Size', 'filestore.bytes', $query),
        new QueryColumnHeader('Date', 'filestore.date', $query),
        new ColumnHeader('User'),
        new ColumnHeader('')
    ]
);

echo $table;
