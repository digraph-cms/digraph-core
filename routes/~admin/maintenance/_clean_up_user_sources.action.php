<h1>Clean up user sources</h1>
<p>
    This tool allows you to clean up user authentication sources that are not supported by your current configuration.
    These can occur when you remove or rename an authentication source in config.
</p>

<?php

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
$query = DB::query()->from('user_source');
foreach (Users::sources() as $source) {
    foreach ($source->providers() as $provider) {
        $query->where('(source <> ? OR provider <> ?)', [$source->name(), $provider]);
    }
}

// display orphaned auth sources in table
$table = new QueryTable(
    $query,
    function ($row): array {
        $user = Users::get($row['user_uuid']);
        return [
            $user,
            $row['source'],
            $row['provider'],
            $row['provider_id'],
            Format::date($row['created']),
            new SingleButton(
                'Delete',
                function () use ($row) {
                    if (DB::query()
                        ->delete('user_source', $row['id'])
                        ->execute()
                    ) {
                        Notifications::flashConfirmation('Deleted authentication source');
                    } else {
                        Notifications::flashError('Error deleting authentication source');
                    }
                    throw new RefreshException();
                },
                ['error']
            )
        ];
    },
    [
        new ColumnHeader('User'),
        new QueryColumnHeader('Source', 'source', $query),
        new QueryColumnHeader('Provider', 'provider', $query),
        new ColumnHeader('Provider ID'),
        new QueryColumnHeader('Created', 'created', $query),
        new ColumnHeader('')
    ]
);

echo $table;
