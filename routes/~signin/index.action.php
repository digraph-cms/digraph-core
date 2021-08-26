<h1>Sign in</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Templates;
use DigraphCMS\Users\Users;

Cookies::require(['auth', 'csrf']);

// handle single signin option by bouncing directly to it
$urls = Users::allSigninURLs(Context::arg('bounce'));
if (count($urls) == 1) {
    Context::response()->redirect(reset($urls));
    return;
}

// list signin options
echo Templates::render(
    'signin/options.php',
    [
        'bounce' => Context::arg('bounce')
    ]
);
