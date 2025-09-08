<h1>Log in</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Security\Security;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

// require captcha
Security::requireSecurityCheck(false);

// require the necessary cookies
Cookies::required(['system', 'auth', 'csrf']);

// get bounce arg and turn it into a URL (which verifies it's in-site)
$bounce = Context::arg_string('_bounce', true);
if ($bounce) {
    try {
        $bounce = new URL($bounce);
        Cookies::set('auth', 'bounce', $bounce->__toString());
    } catch (Throwable $th) {
        Cookies::unset('auth', 'bounce');
        Security::flag('potentially malicious bounce URL');
        ExceptionLog::log($th);
        $bounce = null;
    }
    $url = Context::url();
    $url->unsetArg('_bounce');
    throw new RedirectException($url);
}

// handle single signin option by bouncing directly to it
$urls = Users::allSigninURLs(Context::arg_string('_bounce', true));
if (count($urls) == 1 && !Context::arg_bool('_noredirect')) {
    Context::response()->redirect(reset($urls));
    return;
}

// error for zero options
if (count($urls) == 0) {
    Notifications::printError("No signin sources configured");
    return;
}

// list signin options
echo Templates::render('signin/options.php');