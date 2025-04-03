<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Exception;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\Security\Security;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;

/** @var \DigraphCMS\Users\OAuth2UserSource */
$source = Users::source('oauth2');
$providerName = Context::arg('_provider');
$provider = $source->provider($providerName);

if (Context::arg('error')) {
    // Got an error, probably user denied access or authorization code is expired
    Notifications::printError(
        'OAuth Error: %s <a href="%s">Restart the sign-in process?</a>',
        htmlspecialchars(Context::arg('error'), ENT_QUOTES, 'UTF-8'),
        Users::signinUrl(null)
    );
    return;
} elseif (!Context::arg('code')) {
    // Redirect to provider authorization URL so we will get a code on the next pageview
    $authURL = $provider->getAuthorizationUrl([
        'scope' => Config::get("user_sources.oauth2.providers." . $providerName . ".scope")
    ]);
    Cookies::set('csrf', 'oauth2state', $provider->getState());
    throw new ArbitraryRedirectException($authURL);
} elseif (!Context::arg('state') || Context::arg('state') !== Cookies::get('csrf', 'oauth2state')) {
    // State is invalid, possible CSRF attack
    Security::flag('Invalid OAuth state');
    ExceptionLog::log(
        new Exception('Invalid OAuth state', [
            'state' => Context::arg('state'),
            'csrf' => Cookies::get('csrf', 'oauth2state')
        ])
        );
    Notifications::printError(
        'Invalid OAuth state. <a href="%s">Please try again</a>.',
        Users::signinUrl(null)
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
