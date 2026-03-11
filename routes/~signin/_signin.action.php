<?php

use DigraphCMS\Cache\RateLimit;
use DigraphCMS\Content\Router;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Exception;
use DigraphCMS\ExceptionLog;
use DigraphCMS\HTTP\ArbitraryRedirectException;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\Security\Security;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

// rate limit
RateLimit::limit('signin', '_signin', 1);

// require captcha
Security::requireSecurityCheck();

// require the necessary cookies
Cookies::required(['system', 'ui', 'auth', 'csrf']);

// source and provider must exist
$sourceName = Context::arg_string('_source');
$source = Users::source(Context::arg_string('_source'));
if (!$source) {
    Security::flag('Unknown login source');
    throw new HttpError(400, 'Unknown source');
}
/** @var string */
$provider = Context::arg_string('_provider');
if (!$source->providerActive($provider)) {
    Security::flag('Unknown login provider');
    throw new HttpError(400, 'Unknown provider');
}

// include source handler file
try {
    Router::include('_source/' . $source->name() . '.signin.php');
}
catch (RedirectException | RefreshException | ArbitraryRedirectException $r) {
    throw $r;
}
catch (Throwable $th) {
    ExceptionLog::log($th);
    $url = Users::signinUrl(null);
    $url->setArg('_noredirect', true);
    Notifications::printError(sprintf(
        'Sign-in handler encountered an error. <a href="%s">Please try again</a>.',
        $url,
    ));
    return;
}

// handle signin within digraph
if (Context::data('signin_provider_id')) {

    /** @var string */
    $providerID = Context::data('signin_provider_id');
    $fullSourceTitle = $source->providerName($provider) . ' via ' . $source->title();
    if ($user = $source->lookupUser($provider, $providerID)) {
        // user is signed in as a different user than who this signin is already associated with
        if (Session::user() && Session::user() != $user) {
            Notifications::printError(sprintf(
                "That %s signin is already associated with a different account on this site. To associate your current account %s with this %s signin, you need to first sign into the other account and remove it there.",
                $fullSourceTitle,
                Users::current(),
                $fullSourceTitle,
            ));
            Context::response()->status(403);
            return;
        }
        // user is signed in, link this pair to their account
        Session::authenticate($user, 'Signed in with ' . $fullSourceTitle);
    }
    else {
        // this provider/id pair is not tied to a user
        // either link it to the current user or create a new user
        if ($user = Session::user()) {
            // user is signed in, link this pair to their account
            $source->authorizeUser($user, $provider, $providerID);
            // send emails indicating that a new auth method was added
            Emails::queue(Email::newForUser_all(
                'service',
                Users::current(),
                "New sign-in method added to your account",
                new RichContent(
                    Templates::render(
                        '/email/account/new-sign-in-method.php',
                        [
                            'user'   => Users::current(),
                            'source' => $fullSourceTitle,
                        ],
                    ),
                ),
            ));
        }
        else {
            // user is not signed in, create a new user and link pair to it
            DB::beginTransaction();
            $user = new User();
            Dispatcher::dispatchEvent('onCreateUser', [$user, $source->name(), $provider, $providerID]);
            Dispatcher::dispatchEvent('onCreateUser_' . $sourceName, [$user, $provider, $providerID]);
            Dispatcher::dispatchEvent('onCreateUser_' . $sourceName . '_' . $provider, [$user, $providerID]);
            $user->insert();
            $source->authorizeUser($user->uuid(), $provider, $providerID);
            // sign in as new user
            Session::authenticate($user->uuid(), 'Signed up with ' . $fullSourceTitle);
            DB::commit();
        }
    }

    // include post-signin handler file
    try {
        Router::include('_source/' . $source->name() . '.after.php');
    }
    catch (Throwable $th) {
        // silently log errors here so they don't interrupt signin
        ExceptionLog::log($th);
    }

    // if there is a bounce target, try to make it into a URL and redirect
    $bounce = Cookies::get('ui', 'auth_bounce');
    if ($bounce) {
        try {
            $bounce = new URL($bounce);
        }
        catch (Throwable $th) {
            Security::flag('potentially malicious bounce URL (after signin)');
            ExceptionLog::log($th);
            $bounce = null;
        }
    }

    // as a fallback redirect to either profile page or sign in page
    $bounce = $bounce
        ?: Users::current()?->profile()
        ?: new URL('/signin/');

    // redirect to bounce target. Note that it uses the response->redirect()
    // method directly, because all this happens in a try/catch block and the
    // RedirectException would just get logged
    if ($bounce) {
        Context::response()->redirect((string) $bounce->__toString(), targetFrame: '_top');
        Cookies::unset('ui', 'auth_bounce');
        return;
    }

    // if we get here, something went wrong
    ExceptionLog::log(
        new Exception(
            "Sign-in handler failed to redirect to bounce target",
            [
                'bounce' => $bounce,
            ],
        ),
    );
    Notifications::printNotice(sprintf(
        "Sign-in successful, but failed to redirect to bounce target. Please <a href=\"%s\">click here</a> to continue.",
        $bounce ?: new URL('/')
    ));
}
