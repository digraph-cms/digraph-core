<?php

use DigraphCMS\UI\DataTables\UserTable;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

$users = Users::select();

echo "<h1>All users</h1>";

$table = new UserTable(
    $users,
    function (User $user) {
        return [
            $user->uuid()
        ];
    }
);
echo $table;
