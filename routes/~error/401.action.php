<h1>Sign-in required</h1>
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
if (!$user) {
    echo "<p>You are not signed in, you can try <a href='$signinUrl'>signing in</a> if you have an account.</p>";
}

Router::include('/~error/trace.php');
