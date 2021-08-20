<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\DataTables\PageTable;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Users;

$user = Users::get(Context::url()->action());
if (!$user) {
    throw new HttpError(404, 'User not found');
}

echo "<h1>" . $user->name() . "</h1>";

$groups = $user->groups();
if (count($groups) > 1) {
    echo "<section class='user-groups'>";
    echo "<h2>User groups</h2>";
    echo "<ul>" . implode(PHP_EOL, array_map(
        function (Group $group) {
            return "<li>$group</li>";
        },
        $user->groups()
    )) . "</ul>";
    echo "</section>";
}

$pages = Pages::select()
    ->where('created_by = :uuid OR updated_by = :uuid', ['uuid' => $user->uuid()])
    ->order('updated DESC');
if ($pages->count()) {
    echo "<section class='user-pages'>";
    echo "<h2>Pages created/modified</h2>";
    echo new PageTable($pages);
    echo "</section>";
}
