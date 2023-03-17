<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Pagination\UserTable;
use DigraphCMS\Users\Users;

$group = Users::group(Context::url()->action());
if (!$group) {
    throw new HttpError(404);
}

echo "<h1>" . ucfirst($group->name()) . "</h1>";

$table = new UserTable(Users::groupMembers($group->uuid()));
echo $table;
