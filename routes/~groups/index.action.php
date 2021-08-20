<?php

use DigraphCMS\Users\Users;

echo '<h1>User groups</h1>';
echo '<ul>';
foreach (Users::allGroups() as $group) {
    echo "<li>$group</li>";
}
echo '</ul>';
