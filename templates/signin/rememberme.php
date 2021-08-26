<h2>Stay signed in?</h2>
<p>Would you like to stay signed in with this browser, and reduce the number of sign in requests for this computer? You should not choose to stay signed in on a shared computer.</p>

<?php

use DigraphCMS\Context;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;

echo new ButtonMenu(null, [
    new ButtonMenuButton(
        'Trust this computer',
        function () {
            Context::response()->redirect(Context::fields()['yes_url']);
        }
    ),
    new ButtonMenuButton(
        'Sign out when I close my browser',
        function () {
            Context::response()->redirect(Context::fields()['no_url']);
        }
    )
]);
