<?php

use DigraphCMS\Content\Pages;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PageTable;
use DigraphCMS\Users\Group;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('user'));
if (!$user) {
    throw new HttpError(404);
}

echo "<h1>User profile: " . $user->name() . "</h1>";

printf(
    "<p>Registered %s</p>",
    Format::date($user->created())
);

Router::include('profile_top/*.php');

if ($groups = $user->groups()) {
    echo "<section class='user-groups'>";
    echo "<h2>Member of</h2>";
    echo "<ul>" . implode(PHP_EOL, array_map(
        function (Group $group) {
            return "<li>$group</li>";
        },
        $user->groups()
    )) . "</ul>";
    echo "</section>";
}

$pages = Pages::select()
    ->where('created_by = ? OR updated_by = ?', [$user->uuid(), $user->uuid()])
    ->order('updated DESC');
if ($pages->count()) {
    echo "<section class='user-pages'>";
    echo "<h2>Pages created/modified</h2>";
    echo new PageTable($pages);
    echo "</section>";
}

Router::include('profile_bottom/*.php');
