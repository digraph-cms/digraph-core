<h1>Log in</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;
use Joby\Smol\Sentry\Severity;

// require the necessary cookies and disable indexing
Cookies::required(['system', 'ui', 'auth', 'csrf']);
Context::response()->setNoIndex();

// get bounce arg and turn it into a URL (which verifies it's in-site)
$bounce = Context::arg_string('_bounce', true);
if ($bounce) {
    try {
        $bounce = new URL($bounce);
        Cookies::set('ui', 'auth_bounce', $bounce->__toString());
    }
    catch (Throwable $th) {
        Cookies::unset('ui', 'auth_bounce');
        Context::sentry()->signal('bounce_manipulation', Severity::Malicious);
        $bounce = null;
    }
    $url = Context::url();
    $url->unsetArg('_bounce');
    throw new RedirectException($url);
}

// handle single signin option by bouncing directly to it
$urls = Users::allSigninURLs();
if (count($urls) == 1 && !Context::arg_bool('_noredirect', true)) {
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
