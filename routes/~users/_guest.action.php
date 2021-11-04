<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\UI\DataTables\PageTable;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Users;

Context::response()->enableCache();

$user = Users::guest();

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
    ->where('created_by is null OR updated_by is null')
    ->order('updated DESC');
if ($pages->count()) {
    echo "<section class='user-pages'>";
    echo "<h2>Pages created/modified</h2>";
    echo new PageTable($pages);
    echo "</section>";
}
