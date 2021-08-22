<h1>Flash notifications</h1>
<?php

use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;

$random = Users::randomName();

Notifications::flashNotice($random);

echo "<p>On your next pageview you should get a notification that says \"$random\"</p>";
