<h1>Access denied</h1>
<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

echo "<p>You have been denied access to this page.</p>";
if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

$user = Users::current();
$signinUrl = Users::signinUrl(Context::request()->originalUrl());
$signoutLink = Users::signoutLink('sign out', $signinUrl);
echo "<p>You are currently signed in as $user. If this is not you, please $signoutLink and try again.<p>";

Router::include('/~error/trace.php');
