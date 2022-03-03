<h1>Authorization required</h1>
<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

$user = Users::current();
if (!$user) {
    $signinUrl = Users::signinUrl(Context::request()->originalUrl());
    echo "<p>You are not signed in, but you may try <a href='$signinUrl'>signing in</a> if you have an account.</p>";
}else {
    echo "<p>You have been denied access to this page.</p>";
}

Router::include('/~error/trace.php');
