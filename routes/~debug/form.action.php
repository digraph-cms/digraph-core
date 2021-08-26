<h1>Forms</h1>
<?php

use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;

echo new ButtonMenu('Test button menu', [
    new ButtonMenuButton(
        'Option A',
        function () {
            var_dump('clicked A');
        }
    ),
    new ButtonMenuButton(
        'Option B',
        function () {
            var_dump('clicked B');
        }
    )
]);
