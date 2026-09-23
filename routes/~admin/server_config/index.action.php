<h1>Server config info</h1>

<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Templates;

ActionMenu::hide();

$info = [];

$info['PHP version'] = phpversion();
$info['PHP extensions'] = implode(', ', get_loaded_extensions());

echo '<table>';
foreach ($info as $key => $value)
    printf('<tr><td><strong>%s</strong></td><td>%s</td></tr>', htmlentities($key), htmlentities($value));
echo '</table>';

echo Templates::render(
    'content/toc.php',
);
