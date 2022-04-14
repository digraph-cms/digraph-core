<h1>Debugging tools</h1>

<p>
    The following tools can be used for debugging and developing.
    They are mostly not useful for building anything real, and may disappear at any time.
</p>

<?php

use DigraphCMS\UI\Templates;

echo Templates::render(
    'content/toc.php',
);
