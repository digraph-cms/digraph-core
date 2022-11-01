<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

?>
<h1>Broken link notice</h1>

<p>
    A broken link was just discovered on <code><a href="<?php echo Context::url(); ?>"><?php echo Context::url(); ?></a></code>
</p>

<p>
    A link to the URL <code><?php echo Context::fields()['broken_url']; ?></code> could not be resolved.
    An automatic Wayback Machine link to what used to exist at that URL will be used to replace it if possible.
</p>

<p>
    <a href="<?php
                $url = new URL('/wayback/_manage.html');
                $url->arg('url', Context::fields()['broken_url']);
                $url->arg('context', Context::url()->pathString());
                ?>">Manage settings for this link/page combination</a>
</p>