<h1>Test pages</h1>

<p>
    The following pages are for testing purposes only.
    They are not tracked in source control and are likely exclusive to this site.
</p>

<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Templates;

ActionMenu::hide();

echo Templates::render(
    'content/toc.php',
);
