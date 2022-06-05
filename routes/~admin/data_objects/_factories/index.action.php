<h1>Data object factories</h1>

<p>
    Each factory represents one database table, and holds one general type of object.
    Factory trash is emptied daily, permanently removing anything that has been deleted for more than a week.
</p>
<?php

use DigraphCMS\Config;
use DigraphCMS\DataObjects\DataObjects;
use DigraphCMS\UI\DataTables\ArrayTable;

echo new ArrayTable(
    Config::get('data_objects'),
    function ($name, $cnf) {
        $factory = DataObjects::factory($name);
        return [
            $name,
            get_class($factory),
            $factory->query()->count(),
            $factory->query()->includeDeleted(true)->count(),
            $factory->checkEnvironment()
                ? 'OK'
                : 'Needs schema update'
        ];
    },
    [
        'Name',
        'Class',
        'Object count',
        'Trash size',
        'DB Status'
    ]
);
