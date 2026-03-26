<h1>Too Many Requests</h1>
<p>Too many requests for this resource have been sent in a given amount of time. Please try again later.</p>

<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

Router::include('/~error/trace.php');
