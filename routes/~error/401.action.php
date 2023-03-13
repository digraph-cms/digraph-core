<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

$user = Users::current();
if (!$user) {
    echo "<h1>Sign in to continue</h1>";
    $signinUrl = Users::signinUrl(Context::request()->originalUrl());
    echo "<p>You must <a href='$signinUrl' class='button'>sign in</a> to view this page</p>";
}else {
    echo "<h1>Access denied</h1>";
    echo "<p>You have been denied access to this page.</p>";
}

if ($message = Context::data('error_message')) {
    echo "<p><small>$message</small></p>";
}

Router::include('/~error/trace.php');
