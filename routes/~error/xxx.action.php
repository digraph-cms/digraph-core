<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;

echo "<h1>Unknown error</h1>";
echo "<p>An unhandled exception occurred.</p>";

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

Router::include('/~error/trace.php');
