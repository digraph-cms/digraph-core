<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;

echo "<h1>Server error</h1>";
echo "<p>An unhandled exception occurred.</p>";

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

Router::include('trace.php');
