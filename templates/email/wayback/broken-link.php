<?php

use DigraphCMS\Context;

$brokenURL = Context::fields()['broken_url'];
?>
<h1>Broken link notice</h1>

<p>
    A broken link was just discovered on <code><a href="<?php echo Context::url(); ?>"><?php echo Context::url(); ?></a></code>.
</p>

<p>
    A link to the URL <code><?php echo $brokenURL; ?></code> could not be resolved.
    An automatic Wayback Machine link to what used to exist at that URL will be used to replace it if possible.
</p>