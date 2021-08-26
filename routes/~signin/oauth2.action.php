<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

Cookies::require(['auth', 'csrf']);

/** @var \DigraphCMS\Users\OAuth\OAuth2UserSource */
$source = Users::source('oauth2');
$url = Context::url();
$bounce = Context::arg('bounce');
if ($bounce) {
    $bounce = new URL($bounce);
}

// no provider is specified
if (!Context::arg('_provider')) {
    // list all providers
    echo '<h1>' . $source->title() . '</h1>';
    echo "<ul class='oauth-providers'>";
    foreach ($source->providers() as $name) {
        $url = new URL("&_provider=$name");
        echo "<li class='oauth-provider oauth-provider_$name'>";
        echo "<a href='$url'>" . Config::get("oauth2.providers.$name.name") . "</a>";
        echo "</li>";
    }
    echo "</ul>";
    return;
}

// display individual provider
$name = Context::arg('_provider');
$provider = $source->provider($name, $bounce);
echo "<h1>" . Config::get("oauth2.providers.$name.name") . "</h1>";

if (!empty(Context::arg('error'))) {
    // Got an error, probably user denied access
    Notifications::printError(
        'Got error: ' . htmlspecialchars(Context::arg('error'), ENT_QUOTES, 'UTF-8') .
            "<br><a href='" . new URL('/~signin/') . "'>Restart the sign-in process?</a>"
    );
    Context::response()->status(500);
} elseif (empty(Context::arg('code'))) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => Config::get("oauth2.providers.$name.scope")
    ]);
    Context::response()->redirect($authUrl);
    Cookies::set('csrf', 'oauth2state', $provider->getState());
    return;
} elseif (empty(Context::arg('state')) || Context::arg('state') !== Cookies::get('csrf', 'oauth2state')) {
    // State is invalid, possible CSRF attack in progress
    Cookies::unset('csrf', 'oauth2state');
    throw new HttpError(500, "Invalid OAuth 2 state");
} else {
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
    // get access token and resource owner, pass to user source for sign-in
    $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => Context::arg('code')
    ]);
    Cookies::unset('csrf', 'oauth2state');
    $resourceOwner = $provider->getResourceOwner($accessToken);
    $id = $resourceOwner->getID();
    // try to look up user by provider/ID, if found sign in as that user,
    // otherwise create a new user and attach this provider/ID to them
    if ($userID = $source->lookupUser($name, $id)) {
        // this provider/id pair is tied to a user, sign in as that user
        Session::authenticate($userID, 'Signed in with ' . Config::get("oauth2.providers.$name.name"), Context::arg('rememberme') == 'y');
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
            Session::authenticate($user->uuid(), 'Signed up with ' . Config::get("oauth2.providers.$name.name"), Context::arg('rememberme') == 'y');
        }
        DB::commit();
    }
    // look through resource owner array for emails, add them to user
    $user = Users::current();
    $ownerData = $resourceOwner->toArray();
    foreach ($ownerData as $key => $value) {
        if (stripos($key, 'email') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $count = count($user->emails());
            $user->addEmail($value, 'Added from OAuth ' . Config::get("oauth2.providers.$name.name"));
        }
    }
    $user->update();
    // if there is a bounce target, try to make it into a URL and redirect
    if ($bounce) {
        Context::response()->redirect($bounce);
    } else {
        // otherwise redirect to profile page
        Context::response()->redirect(
            $user->profile()
        );
    }
}
