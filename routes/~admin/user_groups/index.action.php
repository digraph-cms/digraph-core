<h1>User group management</h1>
<?php

use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Users;

$groups = Users::allGroups();

$table = new PaginatedTable(
    $groups,
    function (Group $group) {
        return [
            sprintf(
                '<a href="%s">%s</a>',
                new URL('manage:' . $group->uuid()),
                $group->name()
            ),
            Users::groupMembers($group->uuid())->count(),
            in_array($group->uuid(), ['admins', 'editors']) ? '' : sprintf(
                '<a href="%s">empty</a>',
                new URL('empty:' . $group->uuid())
            ),
            in_array($group->uuid(), ['admins', 'editors']) ? '' : sprintf(
                '<a href="%s">delete</a>',
                new URL('delete:' . $group->uuid())
            )
        ];
    }
);

echo $table;
