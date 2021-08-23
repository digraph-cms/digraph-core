<?php

use DigraphCMS\Context;
use DigraphCMS\Users\Users;

Context::response()->enableCache();

echo '<h1>User groups</h1>';
echo '<ul>';
foreach (Users::allGroups() as $group) {
    echo "<li>$group</li>";
}
echo '</ul>';
