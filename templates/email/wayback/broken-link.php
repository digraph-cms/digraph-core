<?php

use DigraphCMS\Context;

?>
<h1>Broken link notice</h1>

<p>
    A broken link was just discovered on <code><a href="<?php echo Context::url(); ?>"><?php echo Context::url(); ?></a></code>.
</p>

<p>
    A link to the URL <code><?php echo Context::fields()['broken_url']; ?></code> could not be resolved.
    An automatic Wayback Machine link to what used to exist at that URL will be used to replace it if possible.
</p>

<p>
    Technical info:<br>
    HTTP status: <code><?php echo Context::fields()['http_status']; ?></code><br>
    cURL error number: <code><?php echo Context::fields()['curl_errno']; ?></code><br>
    cURL error message: <code><?php echo Context::fields()['curl_error']; ?></code>
</p>