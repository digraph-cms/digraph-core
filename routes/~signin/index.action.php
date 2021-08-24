<?php

use DigraphCMS\Context;
use DigraphCMS\Users\Users;

$sources = Users::sources();
$bounce = Context::arg('bounce');

// handle missing or single source cases
if (!$sources) {
    throw new \Exception("No user sources are available");
} elseif (count($sources) == 1) {
    $source = reset($sources);
    Context::response()->redirect($source->signinUrl($bounce));
    return;
}

// list signin options
echo "<ul class='signin-sources'>";
foreach ($sources as $source) {
    $name = $source->name();
    $title = $source->title();
    $url = $source->signinUrl($bounce);
    echo "<li class='signin-source signin-source_$name'>";
    echo "<a href='$url'>$title</a>";
    echo "</li>";
}
echo "</ul>";
