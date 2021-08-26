<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

Cookies::require(['auth', 'csrf']);

/** @var \DigraphCMS\Users\CASUserSource */
$cas = Users::source('cas');
$bounce = Context::arg('bounce');

// no provider is specified
if (!Context::arg('_provider')) {
    Context::response()->redirect(Users::signinUrl($bounce));
}

// turn bounce string into URL
if ($bounce) {
    $bounce = new URL($bounce);
}

// display individual provider
$provider = Context::arg('_provider');
$config = Config::get("cas.providers.$provider");
echo "<h1>" . $config['name'] . "</h1>";

if (!@$config['mock_cas_user']) {
    // BEGIN CONFIGURING CAS
    // fudge $_SERVER values if you're in an environment where CAS can't tell you
    // have HTTPS enabled, such as a proxy server handling SSL
    if (@$config['fixhttpsproblems']) {
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['HTTPS'] = 'on';
    }

    //set up client, initialize phpCAS
    switch (@$config['version']) {
        case 'CAS_VERSION_1_0':
            $version = CAS_VERSION_1_0;
            break;
        case 'CAS_VERSION_2_0':
            $version = CAS_VERSION_2_0;
            break;
        case 'CAS_VERSION_3_0':
            $version = CAS_VERSION_3_0;
            break;
        default:
            $version = CAS_VERSION_2_0;
    }
    \phpCAS::client(
        CAS_VERSION_2_0,
        $config['server'],
        intval($config['port']),
        $config['context']
    );

    //set up configured config calls
    if (@$config['setnocasservervalidation']) {
        \phpCAS::setNoCasServerValidation();
    }

    // TRY TO SIGN IN
    $id = \phpCAS::getUser();
} else {
    // USE MOCK CAS USER
    $id = $config['mock_cas_user'];
}

// There will be errors thrown if that fails, so assume it worked

// prompt for whether we should remember user
if (Session::user() == 'guest' && !Context::arg('rememberme')) {
    echo Templates::render(
        '/signin/rememberme.php',
        [
            'yes_url' => new URL('&rememberme=y'),
            'no_url' => new URL('&rememberme=n')
        ]
    );
    return;
}

// handle signin within digraph
if ($userID = $cas->lookupUser($provider, $id)) {
    // user is signed in, link this pair to their account
    Session::authenticate($userID, 'Signed in with ' . Config::get("cas.providers.$name.name"), Context::arg('rememberme') == 'y');
} else {
    // this provider/id pair is not tied to a user
    // either link it to the current user or create a new user
    DB::beginTransaction();
    if ($user = Users::current()) {
        // user is signed in, link this pair to their account
        $source->authorizeUser($name, $id, $user->uuid());
    } else {
        // user is not signed in, create a new user and link pair to it
        $user = new User();
        $user->insert();
        $source->authorizeUser($name, $id, $user->uuid());
        // sign in as new user
        Session::authenticate($user->uuid(), 'Signed up with ' . Config::get("cas.providers.$name.name"), Context::arg('rememberme') == 'y');
    }
    DB::commit();
}

// if there is a bounce target, try to make it into a URL and redirect
if ($bounce) {
    Context::response()->redirect($bounce);
} else {
    // otherwise redirect to profile page
    Context::response()->redirect(
        $user->profile()
    );
}
