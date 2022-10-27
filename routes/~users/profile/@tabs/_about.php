<?php

use DigraphCMS\UI\Format;
use DigraphCMS\Users\Group;

printf(
    "<p>Account registered %s</p>",
    Format::date($user->created())
);

if (($groups = $user->groups()) && count($groups) > 1) {
    echo "<h1>User groups</h1>";
    echo "<ul>" . implode(PHP_EOL, array_map(
        function (Group $group) {
            return "<li>$group</li>";
        },
        $user->groups()
    )) . "</ul>";
}
