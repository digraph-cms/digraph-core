<?php

use DigraphCMS\Security\Security;
use DigraphCMS\Config;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

$id = Context::arg('frame') ?? 'captcha-interface';

echo '<div id="' . $id . '">';
if (Context::arg('frame')) {
    echo '<div data-target="' . Context::arg('frame') . '" class="card">';
}

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
Router::include('_services/' . Config::get('captcha.service') . '.php');

if (Context::arg('frame')) {
    echo '</div>';
}
echo '</div>';
