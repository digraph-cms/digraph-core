<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\DataTables\PageTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('uuid'));
if (!$user) {
    throw new HttpError(404, 'User not found');
}

echo "<h1>" . $user->name() . "</h1>";

echo "<section class='user-groups'>";
echo "<h2>Member of</h2>";
echo "<ul>" . implode(PHP_EOL, array_map(
    function ($group) {
        $url = new URL('/~user/group.html?group=' . $group);
        return "<li><a href='$url'>$group</a></li>";
    },
    $user->groups()
)) . "</ul>";
echo "</section>";

$pages = Pages::select()
    ->where('created_by = :uuid OR updated_by = :uuid', ['uuid' => $user->uuid()])
    ->order('updated DESC');
if ($pages->count()) {
    echo "<section class='user-pages'>";
    echo "<h2>Pages created/modified</h2>";
    echo new PageTable($pages);
    echo "</section>";
}
