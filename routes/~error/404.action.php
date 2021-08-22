<h1>Not found</h1>
<p>The requested URL could not be found.</p>

<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

Router::include('trace.php');