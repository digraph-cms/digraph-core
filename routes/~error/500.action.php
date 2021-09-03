<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;

echo "<h1>Error</h1>";

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
} else {
    echo "<p>No error message provided</p>";
}

Router::include('/~error/trace.php');
