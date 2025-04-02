<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\Users\Users;

$user = Users::current();
if (!$user) {
    $sources = Users::sources();
    $providers = [];
    foreach ($sources as $source) {
        foreach ($source->providers() as $p) {
            if ($source->providerActive($p)) {
                $providers[] = $source->providerName($p);
            }
        }
    }
    if (count($providers) <= 1) {
        $source = reset($providers);
        echo "<h1>$source sign in required</h1>";
    } else {
        $provider_names = array_map(fn(string $p) => '<strong>' . $p . '</strong>', $providers);
        $last = array_pop($provider_names);
        $provider_names[] = array_pop($provider_names) . ' or ' . $last;
        $provider_names = implode(', ', $provider_names);
        echo "<h1>Sign in required</h1>";
        printf('<p>To view this page you will need to sign in with your %s account.</p>', $provider_names);
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
