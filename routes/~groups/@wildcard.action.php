<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\DataTables\UserTable;
use DigraphCMS\Users\User;
use DigraphCMS\Users\UserSelect;

Context::response()->enableCache();

$group = Context::url()->action();

$users = new UserSelect(
    DB::query()
        ->from('user_groups')
        ->select('user.*')
        ->leftJoin('user on group_user = user_uuid')
        ->where('group_name = ?', [$group])
);
if (!$users->count()) {
    throw new HttpError(404, 'Group empty or not found');
}

echo "<h1>" . ucfirst($group) . "</h1>";

$table = new UserTable(
    $users,
    function (User $user) {
        return [
            $user->uuid()
        ];
    }
);
echo $table;
