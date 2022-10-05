<h1>Email tools</h1>

<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Templates;

ActionMenu::hide();

echo Templates::render(
    'content/toc.php',
);
