<?php

use DigraphCMS\UI\Pagination\UserTable;
use DigraphCMS\Users\Users;

$users = Users::select()
    ->order(null)
    ->order('created DESC');

echo "<h1>All users</h1>";

$table = new UserTable($users);
echo $table;
