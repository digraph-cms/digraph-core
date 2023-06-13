<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('user'));
$token = Context::arg('token');

if (!$user) throw new HttpError(404);

// try to find token in email list
foreach ($user['emails'] as $i => $row) {
    if (@$row['verification']['token'] == $token) {
        $user->verifyEmail($row['address']);
        $user->update();
        if ($user->uuid() == Session::user()) {
            // signed in as this user, bounce to email address page
            Notifications::flashConfirmation('Email address verified: ' . $row['address']);
            throw new RedirectException(new URL('/users/profile/email_addresses.html'));
        } elseif (!Session::user()) {
            // user is not signed in, prompt them to sign in
            Notifications::printConfirmation('Email address verified: ' . $row['address']);
            echo Users::signinUrl(new URL('/'))->html();
        } else {
            // user is signed in as somebody else? Weird but we'll handle it
            Notifications::printConfirmation('Email address verified: ' . $row['address']);
        }
    }
}

// if nothing was found, display an error
Notifications::printWarning('This email verification link is invalid, expired, or has already been used.');
