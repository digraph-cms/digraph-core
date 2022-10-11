<h1>Debugging tools</h1>

<p>
    The following tools can be used for debugging and developing.
    They are mostly not useful for building anything real, and may disappear at any time.
</p>

<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Templates;

ActionMenu::hide();

echo Templates::render(
    'content/toc.php',
);
