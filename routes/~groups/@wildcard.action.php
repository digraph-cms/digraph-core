<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\DataTables\UserTable;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use DigraphCMS\Users\UserSelect;

Context::response()->enableCache();

$group = Users::group(Context::url()->action());
if (!$group) {
    throw new HttpError(404);
}

$users = new UserSelect(
    DB::query()
        ->from('user_group_membership')
        ->select('user.*')
        ->leftJoin('user on user_uuid = user.uuid')
        ->where('group_uuid = ?', [$group->uuid()])
);

echo "<h1>" . ucfirst($group->name()) . "</h1>";

$table = new UserTable(
    $users,
    function (User $user) {
        return [
            $user->uuid()
        ];
    }
);
echo $table;
