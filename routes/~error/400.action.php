<h1>Bad request</h1>
<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}

?>
<p>
    The server cannot or will not process the request due to something that is perceived to be a client error.
    The client should not repeat this request without modification.
</p>
<?php
Router::include('/~error/trace.php');
