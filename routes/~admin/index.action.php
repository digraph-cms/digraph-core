<h1>Admin tools</h1>

<?php

use DigraphCMS\Content\Router;
use DigraphCMS\UI\Templates;

echo Templates::render(
    'content/toc.php',
);

Router::include('sections/*.php');
