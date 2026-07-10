<?php

use DigraphCMS\Cache\RateLimit;
use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

RateLimit::limit('verify_email', 'attempt', 60);
Context::response()->setNoIndex();

$user = Users::get(Context::arg_string('user'));
$token = Context::arg_string('token');

if (!$user)
    throw new HttpError(404);

// try to find token in email list
foreach ($user['emails'] as $i => $row) {
    if (@$row['verification']['token'] == $token) {
        $user->verifyEmail($row['address']);
        $user->update();
        if ($user->uuid() == Session::user()) {
            // signed in as this user, bounce to email address page
            Notifications::flashConfirmation('Email address verified: ' . $row['address']);
            throw new RedirectException(new URL('/users/profile/email_addresses.html'));
        }
        elseif (!Session::user()) {
            // user is not signed in, prompt them to sign in
            Notifications::printConfirmation('Email address verified: ' . $row['address']);
            echo Users::signinLink(new URL('/'));
            return;
        }
        else {
            // user is signed in as somebody else? Weird but we'll handle it
            Notifications::printConfirmationHTML('Email address verified on behalf of ' . $user . ': ' . $row['address']);
            return;
        }
    }
}

// if nothing was found, display an error
Notifications::printWarning('This email verification link is invalid, expired, or has already been used.');
