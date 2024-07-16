<h1>Log in</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Security\Security;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\Users\Users;

// require captcha
Security::requireSecurityCheck();

// require the necessary cookies
Cookies::required(['system', 'auth', 'csrf']);

// handle single signin option by bouncing directly to it
$urls = Users::allSigninURLs(Context::arg('_bounce'));
if (count($urls) == 1 && !Context::arg('_noredirect')) {
    Context::response()->redirect(reset($urls));
    return;
}

// error for zero options
if (count($urls) == 0) {
    Notifications::printError("No signin sources configured");
    return;
}

// list signin options
echo Templates::render(
    'signin/options.php',
    [
        'bounce' => Context::arg('_bounce')
    ]
);