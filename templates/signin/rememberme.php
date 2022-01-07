<h2>Stay signed in?</h2>
<p>Would you like to stay signed in with this browser, and reduce the number of sign in requests for this computer? You should not choose to stay signed in on a shared computer.</p>

<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;

$redirect = null;

echo new ButtonMenu(null, [
    new ButtonMenuButton(
        'Trust this computer',
        function () use (&$redirect) {
            $redirect = Context::fields()['yes_url'];
        },
        ['button--confirmation']
    ),
    new ButtonMenuButton(
        'Sign out when I close my browser',
        function () use (&$redirect) {
            $redirect = Context::fields()['no_url'];
        },
        ['button--info']
    )
]);

if ($redirect) {
    throw new RedirectException($redirect);
}