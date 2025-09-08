<?php

use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Security\CaptchaMisconfigurationException;
use DigraphCMS\Security\Security;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$id = Context::arg_string('frame', true) ?? 'captcha-interface';
if (preg_match('/[^a-z0-9\-_]/i', $id)) throw new HttpError(400, 'Invalid argument');

echo '<div class="captcha-interface" id="' . $id . '">';

if (!Security::flagged()) {
    if (Context::arg_string('bounce', true)) {
        try {
            $bounce = new URL(Context::arg_string('bounce'));
        } catch (Throwable $th) {
            Security::flag('potentially malicious bounce URL: ' . Context::arg_string('bounce'));
            ExceptionLog::log($th);
            throw new RefreshException();
        }
        throw new RedirectException($bounce, targetFrame: "_frame");
    }
    Notifications::printConfirmation('No CAPTCHA is required for you at this time.');
    if ($id) {
        echo '</div>';
    }
    return;
}

Context::response()->template('captcha.php');

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
