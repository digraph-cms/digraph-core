<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

Notifications::printWarning('Are you sure you would like to delete <strong>' . Context::page()->name() . '</strong>? This action cannot be undone.');

$button = new SingleButton(
    'Confirm deletion',
    function () {
        Context::page()->delete();
        Notifications::flashConfirmation('<strong>' . Context::page()->name() . '</strong> deleted');
        throw new RedirectException(
            Context::page()->parent() ? Context::page()->parent() : new URL('/')
        );
    },
    ['warning']
);

echo $button;
