<h1>Access denied</h1>
<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

echo "<p>You were denied access to this page";
if ($message = Context::data('error_message')) {
    echo " with the message \"$message\"";
}
echo "</p>";

$user = Users::current();
$signinUrl = Users::signinUrl(Context::request()->originalUrl());
$signoutUrl = Users::signoutUrl($signinUrl);
echo "<p>You are currently signed in as $user. If this is not you, please <a href='$signoutUrl'>sign out</a> and try again.<p>";

Router::include('trace.php');
