<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\Redirect;
use DigraphCMS\Users\Users;

$sources = Users::sources();

// handle missing or single source cases
if (!$sources) {
    throw new \Exception("No user sources are available");
} elseif (count($sources) == 1) {
    $source = reset($sources);
    Context::response(new Redirect($source->signinUrl()));
    return;
}

// list signin options
echo "<ul class='signin-sources'>";
foreach ($sources as $source) {
    $name = $source->name();
    $title = $source->title();
    $url = $source->signinUrl();
    echo "<li class='signin-source signin-source_$name'>";
    echo "<a href='$url'>$title</a>";
    echo "</li>";
}
echo "</ul>";
