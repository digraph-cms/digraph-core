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

// list available oauth providers
if (!isset($url->query()['_provider'])) {
    echo '<h1>' . $source->title() . '</h1>';
    echo "<ul class='oauth-providers'>";
    foreach ($source->providers() as $name) {
        echo "<li class='oauth-provider oauth-provider_$name'>";
        echo "<a href='" . new URL("&_provider=$name") . "'>" . Config::get("oauth2.providers.$name.title") . "</a>";
        echo "</li>";
    }
    echo "</ul>";
    return;
}

// display individual provider
$name = $url->query()['_provider'];
$provider = $source->provider($name);
echo "<h1>" . Config::get("oauth2.providers.$name.title") . "</h1>";

if (!isset($_GET['code'])) {
    $authUrl = $provider->getAuthorizationUrl([
        'scope' => Config::get("oauth2.providers.$name.scope")
    ]);
    Context::response()->redirect($authUrl);
    $_SESSION['oauth2state'] = $provider->getState();
    return;
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
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
    // redirect to profile page
    Context::response()->redirect(
        Users::current()->profile()
    );
}
