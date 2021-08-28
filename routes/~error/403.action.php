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
$signoutUrl = Users::signoutUrl($signinUrl);
echo "<p>You are currently signed in as $user. If this is not you, please <a href='$signoutUrl'>sign out</a> and try again.<p>";

Router::include('/~error/trace.php');
