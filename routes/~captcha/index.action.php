<?php

use DigraphCMS\Security\Security;
use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Security\CaptchaMisconfigurationException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$id = Context::arg('frame') ?? 'captcha-interface';

echo '<div id="' . $id . '">';

if (!Security::flagged()) {
    if (Context::arg('bounce')) {
        try {
            $bounce = new URL(Context::arg('bounce'));
        } catch (Throwable $th) {
            Security::flag('potentially malicious bounce URL: ' . Context::arg('bounce'));
            ExceptionLog::log($th);
            throw new RefreshException();
        }
        throw new RedirectException($bounce);
    }
    Notifications::printConfirmation('No CAPTCHA is required for you at this time.');
    if (Context::arg('frame')) {
        echo '</div>';
    }
    return;
}

Context::response()->template('minimal.php');

try {
    Router::include('_services/' . Config::get('captcha.service') . '.php');
} catch (CaptchaMisconfigurationException $e) {
    ExceptionLog::log($e);
    Notifications::printError(sprintf(
        "The configured CAPTCHA service \"%s\" is misconfigured, falling back to \"%s\"",
        Config::get('captcha.service'),
        Config::get('captcha.fallback')
    ));
    Router::include('_services/' . Config::get('captcha.fallback') . '.php');
}

echo '</div>';
