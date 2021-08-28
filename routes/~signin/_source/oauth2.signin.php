<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Users;

/** @var \DigraphCMS\Users\OAuth2UserSource */
$source = Users::source('oauth2');
$providerName = Context::arg('_provider');
$provider = $source->provider($providerName, Context::arg('_bounce'));

if (Context::arg('error')) {
    // Got an error, probably user denied access or authorization code is expired
    $url = new URL('/~signin/');
    $url->arg('_bounce', Context::request()->url()->arg('_bounce'));
    throw new HttpError(
        500,
        'OAuth Error: ' . htmlspecialchars(Context::arg('error'), ENT_QUOTES, 'UTF-8') .
            "<br><a href='$url'>Restart the sign-in process?</a>"
    );
} elseif (!Context::arg('code')) {
    // Redirect to provider authorization URL so we will get a code on the next pageview
    $authURL = $provider->getAuthorizationUrl([
        'scope' => Config::get("user_sources.oauth2.providers." . $providerName . ".scope")
    ]);
    Cookies::set('csrf', 'oauth2state', $provider->getState());
    throw new ArbitraryRedirectException($authURL);
    return;
} elseif (!Context::arg('state') || Context::arg('state') !== Cookies::get('csrf', 'oauth2state')) {
    // State is invalid, possible CSRF attack
    // Cookies::unset('csrf', 'oauth2state');
    $url = new URL('/~signin/');
    $url->arg('_bounce', Context::arg('_bounce'));
    throw new HttpError(
        500,
        'Invalid OAuth state' .
            "<br><a href='$url'>Restart the sign-in process?</a>"
    );
}

/**
 * We need to handle the remember me form *before* we get our access token,
 * otherwise we'll wind up having to go back to GitHub and getting an invalid
 * OAuth state error.
 */
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

/**
 * If we fall through to this point we must have finished doing the sign-in, and
 * we can put their provider id into Context so that it will be available back
 * on _signin.action.php
 */

// get access token and resource owner, pass to user source for sign-in
$accessToken = $provider->getAccessToken('authorization_code', [
    'code' => Context::arg('code')
]);
Cookies::unset('csrf', 'oauth2state');

// get user from provider and save ID into Context
$resourceOwner = $provider->getResourceOwner($accessToken);
Context::data('oauth_resource_owner', $resourceOwner);
Context::data('signin_provider_id', $resourceOwner->getID());
