<h1>Notification demo</h1>
<?php

use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;

$random = Users::randomName();

Notifications::flashNotice($random);
Notifications::notice('This is an informational notice');
Notifications::confirmation('This is a confirmation message');
Notifications::warning('This is a warning message');
Notifications::error('This is an error message');

echo "<p>On your next pageview you should get a notification that says \"$random\"</p>";
