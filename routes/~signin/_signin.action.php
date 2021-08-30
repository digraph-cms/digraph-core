<?php

use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

// require the necessary cookies
Cookies::require(['system', 'auth', 'csrf']);

// provider and source must be specified
if (!Context::arg('_provider') || !Context::arg('_source')) {
    throw new HttpError(404);
}

// source and provider must exist
$sourceName = Context::arg('_source');
/** @var \DigraphCMS\Users\AbstractUserSource */
$source = Users::source(Context::arg('_source'));
$providerName = Context::arg('_provider');
$provider = Context::arg('_provider');
if (!$source || !$source->providerActive($provider)) {
    throw new HttpError(404);
}

// get bounce arg and turn it into a URL (which verifies it's in-site)
$bounce = Context::arg('_bounce');
if ($bounce) {
    $bounce = new URL($bounce);
}

// make breadcrumb right
$bc = new URL('/~signin/');
$bc->arg('_bounce', $bounce);
Breadcrumb::top($bc);

// include source handler file
Router::include('_source/' . $source->name() . '.signin.php');
if (!Context::data('signin_provider_id')) {
    return;
}

// prompt for whether we should remember user
if (!Session::user() && !Context::arg('_rememberme')) {
    echo Templates::render(
        '/signin/rememberme.php',
        [
            'yes_url' => new URL('&_rememberme=y'),
            'no_url' => new URL('&_rememberme=n')
        ]
    );
    return;
}

// handle signin within digraph
$providerID = Context::data('signin_provider_id');
$fullSourceTitle = $source->providerName($provider) . ' via ' . $source->title();
if ($user = $source->lookupUser($provider, $providerID)) {
    if (Session::user() && Session::user() != $user) {
        throw new HttpError(
            403,
            "That $fullSourceTitle account is already associated with a different account on this site. " .
                "To associate the account " . Users::current() . " with this $fullSourceTitle signin, you need to first sign in as " . Users::get($user) . " and remove it from that account."
        );
    }
    // user is signed in, link this pair to their account
    Session::authenticate($user, 'Signed in with ' . $fullSourceTitle, Context::arg('rememberme') == 'y');
    Notifications::flashConfirmation("Welcome back, " . Users::current());
} else {
    // this provider/id pair is not tied to a user
    // either link it to the current user or create a new user
    DB::beginTransaction();
    if ($user = Session::user()) {
        // user is signed in, link this pair to their account
        $source->authorizeUser($user, $providerName, $providerID);
        Notifications::flashConfirmation("Authorized $fullSourceTitle to sign into account " . Users::current());
    } else {
        // user is not signed in, create a new user and link pair to it
        $user = new User();
        $user->insert();
        $source->authorizeUser($user->uuid(), $provider, $providerID);
        // sign in as new user
        Session::authenticate($user->uuid(), 'Signed up with ' . $fullSourceTitle, Context::arg('rememberme') == 'y');
        Notifications::flashConfirmation("Authorized $fullSourceTitle to sign into new account " . Users::current());
    }
    DB::commit();
}

// include post-signin handler file
Router::include('_source/' . $source->name() . '.after.php');

// if there is a bounce target, try to make it into a URL and redirect
if ($bounce) {
    Context::response()->redirect($bounce);
} else {
    // otherwise redirect to profile page
    Context::response()->redirect(
        Users::current()->profile()
    );
}
