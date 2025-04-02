<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\AbstractUserSource;
use DigraphCMS\Users\Users;

$user = Users::current();
if (!$user) {
    $sources = Users::sources();
    if (count($sources) <= 1) {
        $source = reset($sources);
        echo "<h1>" . $source->name() . " sign in required</h1>";
    } else {
        $source_names = array_map(fn(AbstractUserSource $s) => '<strong>' . $s->name() . '</strong>', $sources);
        $last = array_pop($source_names);
        $source_names[] = array_pop($source_names) . ' or ' . $last;
        $source_names = implode(', ', $source_names);
        echo "<h1>Sign in required</h1>";
        printf('<p>To view this page you will need to sign in with a %s account.</p>', $source_names);
    }
    $signinUrl = Users::signinUrl(Context::request()->originalUrl());
    echo "<p><a href='$signinUrl' class='button'>Sign in</a> to view this page</p>";
} else {
    echo "<h1>Access denied</h1>";
    echo "<p>Your account has been denied access to this page.</p>";
}

if ($message = Context::data('error_message')) {
    echo "<p><small>$message</small></p>";
}

Router::include('/~error/trace.php');
