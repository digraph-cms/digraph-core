<?php

use DigraphCMS\Content\Router;

echo "<h1>Database connection failed</h1>";
echo "<p>Could not connect to the database server.</p>";

Router::include('/~error/trace.php');
