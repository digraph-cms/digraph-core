<h1>Email preferences</h1>

<?php

use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;

echo "<div id='email-preferences' class='navigation-frame navigation-frame--stateless' data-target='frame'>";

echo new ButtonMenu('All emails',[
    new ButtonMenuButton('On',function(){}),
    new ButtonMenuButton('Off',function(){})
]);

echo '</div>';
