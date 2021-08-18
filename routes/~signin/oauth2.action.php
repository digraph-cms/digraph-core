<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

/** @var \DigraphCMS\Users\OAuth\OAuth2UserSource */
$source = Users::source('oauth2');
$url = Context::response()->url();
$bounce = $url->arg('bounce');
if ($bounce) {
    $bounce = new URL($bounce);
}

// no provider is specified
if (!isset($url->query()['_provider'])) {
    // if there is only one provider redirect straight to it
    if (count($source->providers()) == 1) {
        $name = $source->providers()[0];
        Context::response()->redirect(new URL("&_provider=$name"));
        return;
    }
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
$name = $url->query()['_provider'];
$provider = $source->provider($name, $bounce);
echo "<h1>" . Config::get("oauth2.providers.$name.name") . "</h1>";

if (!empty($_GET['error'])) {
    // Got an error, probably user denied access
    echo '<p>Got error: ' . htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') . '</p>';
    echo "<p><a href='" . new URL('/~signin/') . "'>Restart the sign-in process?</a></p>";
    Context::response()->status(500);
} elseif (empty($_GET['code'])) {
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => Config::get("oauth2.providers.$name.scope")
    ]);
    Context::response()->redirect($authUrl);
    $_SESSION['oauth2state'] = $provider->getState();
    return;
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
    // State is invalid, possible CSRF attack in progress
    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    throw new \Exception("Invalid OAuth 2 state");
} else {
    // get access token and resource owner, pass to user source for sign-in
    $accessToken = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);
    $resourceOwner = $provider->getResourceOwner($accessToken);
    $id = $resourceOwner->getID();
    // try to look up user by provider/ID, if found sign in as that user,
    // otherwise create a new user and attach this provider/ID to them
    if ($userID = $source->lookupUser($name, $id)) {
        // this provider/id pair is tied to a user, sign in as that user
        Users::setUserID($userID);
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
            Users::setUserID($user->uuid());
        }
        DB::commit();
    }
    // look through resource owner array for emails, add them to user
    $user = Users::current();
    $ownerData = $resourceOwner->toArray();
    foreach ($ownerData as $key => $value) {
        if (stripos($key, 'email') !== false && filter_var($value, FILTER_VALIDATE_EMAIL)) {
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
