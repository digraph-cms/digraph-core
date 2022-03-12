<h2>Trust this browser?</h2>
<p>Would you like to stay signed in with this browser, and reduce the number of sign in requests for this computer? You should not choose to trust the browser on a shared computer.</p>

<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTML\A;
use DigraphCMS\HTTP\RedirectException;

// skip form if we're using php session management
if (Config::get('php_session.enabled')) {
    throw new RedirectException(Context::fields()['no_url']);
}

echo (new A)
    ->addClass('button')
    ->addClass('button--safe')
    ->addChild('Trust this browser')
    ->setHref(Context::fields()['yes_url']);

echo (new A)
    ->addClass('button')
    ->addClass('button--neutral')
    ->addChild('Sign out when I close the browser')
    ->setHref(Context::fields()['no_url']);
