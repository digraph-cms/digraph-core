<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Exception;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\Security\Security;
use DigraphCMS\Session\Cookies;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\OAuth2UserSource;
use DigraphCMS\Users\Users;
use Joby\Smol\Sentry\Severity;
use League\OAuth2\Client\Token\AccessToken;

/** @var OAuth2UserSource */
$source = Users::source('oauth2');
$providerName = Context::arg_string('_provider');
$provider = $source->provider($providerName);

if ($error = Context::arg_string('error', true)) {
    // Got an error, probably user denied access or authorization code is expired
    Notifications::printErrorHTML(sprintf(
        'OAuth Error: %s<br><a href="%s">Restart the sign-in process?</a>',
        htmlspecialchars($error, ENT_QUOTES, 'UTF-8'),
        Users::signinUrl(null),
    ));
    return;
}
elseif (!Context::arg_string('code', true)) {
    // Redirect to provider authorization URL so we will get a code on the next pageview
    $authURL = $provider->getAuthorizationUrl([
        'scope' => Config::get("user_sources.oauth2.providers." . $providerName . ".scope"),
    ]);
    Cookies::set('csrf', 'oauth2state', $provider->getState());
    throw new ArbitraryRedirectException($authURL);
}
elseif (!Context::arg_string('state', true) || Context::arg_string('state', true) !== Cookies::get('csrf', 'oauth2state')) {
    // State is invalid, possible CSRF attack
    Context::sentry()->signal('login_error', Severity::Suspicious);
    ExceptionLog::log(
        new Exception('Invalid OAuth state', [
            'state' => Context::arg_string('state', true),
            'csrf'  => Cookies::get('csrf', 'oauth2state'),
        ]),
    );
    Notifications::printErrorHTML(sprintf(
        'Invalid OAuth state.<br><a href="%s">Please try again</a>.',
        Users::signinUrl(null),
    ));
    return;
}

/**
 * If we fall through to this point we must have finished doing the sign-in, and
 * we can put their provider id into Context so that it will be available back
 * on _signin.action.php
 */

// get access token and resource owner, pass to user source for sign-in
$accessToken = $provider->getAccessToken('authorization_code', [
    'code' => Context::arg_string('code'),
]);
Cookies::unset('csrf', 'oauth2state');

// ensure type of access token
if (!($accessToken instanceof AccessToken))
    throw new RuntimeException("Access token is wrong type");

// get user from provider and save ID into Context
$resourceOwner = $provider->getResourceOwner($accessToken);
Context::data('oauth_resource_owner', $resourceOwner);
Context::data('signin_provider_id', $resourceOwner->getID());
