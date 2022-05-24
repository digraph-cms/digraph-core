<?php

use DigraphCMS\Content\Router;

echo "<h1>Server error</h1>";
echo "<p>An unhandled exception occurred.</p>";

Router::include('/~error/trace.php');
