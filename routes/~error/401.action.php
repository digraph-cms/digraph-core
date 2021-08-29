<h1>Authorization required</h1>
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
if (!$user) {
    echo "<p>You are not signed in, but you may try <a href='$signinUrl'>signing in</a> if you have an account.</p>";
}

Router::include('/~error/trace.php');
