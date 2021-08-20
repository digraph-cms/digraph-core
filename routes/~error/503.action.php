<h1>Service unavailable</h1>
<p>
    This error is usually temporary and commonly caused by the site or database
    being down, overloaded, or undergoing maintenance. Most of the time this
    error should disappear in a few minutes.
</p>

<?php

use DigraphCMS\Context;

if ($message = Context::data('error_message')) {
    echo "<p>$message</p>";
}
