<?php

namespace DigraphCMS\Session;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;

Dispatcher::addSubscriber(Cookies::class);

class Cookies
{
    /**
     * Returns an array of all the cookies that should be used to key output
     * caching. Main reason is to allow separate caching for different UI
     * states, such as flash notification or color settings.
     *
     * @return array
     */
    public static function cacheMutatingCookies(): array
    {
        return [
            'ui' => [
                'color' => static::get('ui', 'color'),
                'flashnotifications' => static::get('ui', 'flashnotifications')
            ]
        ];
    }

    /**
     * Handle cookie required exceptions by delivering a 409 error, to indicate
     * that there is a conflict that must be resolved.
     *
     * @return void
     */
    public static function onException_CookieRequiredError()
    {
        Digraph::buildErrorContent(409.1);
        return true;
    }

    public static function csrfToken($name): string
    {
        return
            static::get('csrf', $name) ??
            static::set('csrf', $name, bin2hex(random_bytes(16)));
    }

    public static function listTypes(): array
    {
        $types = ['system', 'ui', 'auth', 'csrf'];
        Dispatcher::dispatchEvent('onListCookieTypes', [&$types]);
        return $types;
    }

    public static function form(array $types = null, bool $required = false, bool $skipAllowed = false): FormWrapper
    {
        $types = $types ?? static::listTypes();
        if ($skipAllowed) {
            $types = array_filter(
                $types,
                function ($type) {
                    return !Cookies::isAllowed($type);
                }
            );
        }
        $form = new FormWrapper('cookie-authorization');
        $form->button()->setText('Accept the selected cookies');
        $form->token()->setCSRF(false);
        $checkboxes = [];
        foreach ($types as $type) {
            $checkboxes[$type] = $checkbox = new CheckboxField(static::name($type));
            $form->addChild($checkbox);
            $checkbox->setDefault(static::isAllowed($type));
            if ($required) {
                $checkbox
                    ->setDefault(true)
                    ->setRequired(true, "These cookies must be allowed to use this page");
            }
            $checkbox->addTip(static::describe($type));
            if ($expiration = static::expiration($type)) {
                $checkbox->addTip("These cookies automatically expire on your computer after $expiration.");
            } else {
                $checkbox->addTip('These cookies automatically expire on your computer when you close your browser.');
            }
        }
        $form->addCallback(function () use ($checkboxes) {
            foreach ($checkboxes as $type => $field) {
                if ($field->value()) {
                    static::allow($type);
                } else {
                    static::disallow($type);
                }
            }
        });
        return $form;
    }

    public static function isAllowed(string $type): bool
    {
        $allowed = static::get('system', 'cookierules') ?? [];
        return in_array($type, $allowed);
    }

    public static function onCookieName(string $name)
    {
        switch ($name) {
            case 'system':
                return 'Minimum system cookies';
            case 'auth':
                return 'User authorization cookies';
            case 'csrf':
                return 'CSRF protection cookies';
            case 'ui':
                return 'User interface cookies';
        }
        return null;
    }

    public static function onCookieDescribe(string $type, ?string $name)
    {
        if (!$name) {
            switch ($type) {
                case 'csrf':
                    $url = new URL('/~privacy/current_cookies.html');
                    return
                        "These cookies are necessary for the security of some site features. " .
                        "They store temporary tokens that are used in security checks that prevent attackers from executing actions on your behalf or tricking you into performing unintended actions, or to prevent forms from being submitted more than once.";
            }
        } else {
            switch ($type) {
                case 'csrf':
                    return "CSRF cookies store temporary tokens that are used to prevent attackers from executing actions on your behalf, or tricking you into performing actions you did not intend.";
            }
        }
        return null;
    }

    public static function onCookieDescribe_ui_flashnotifications()
    {
        return "Temporarily stores notifications that need to be displayed on the next page you visit, such as confirmations when an action is performed.";
    }

    public static function onCookieDescribe_ui()
    {
        return "UI state cookies, used to keep track of the user interface state and your user interface preferences.";
    }

    public static function onCookieDescribe_ui_color()
    {
        return "Stores your preferences for whether the site should appear in light or dark mode, and whether it should attempt to use colorblindness-friendly colors." .
            "<br>This cookie is only sent to this site when you visit it, and we do not share or retain its data.";
    }

    public static function onCookieDescribe_auth()
    {
        $logURL = new URL('/~user/authentication_log.html');
        return
            "Used by this website for remembering the currently signed in user." .
            "<br>These cookies' contents may be stored, logged, and for security and troubleshooting purposes. They will be associated with personally identifiable information including the date, time, your public IP address, your browser's user agent string, and your account." .
            " Once you sign in you are able to view these records on <a href='$logURL'>your account's authorization log</a>.";
    }

    public static function onCookieDescribe_system()
    {
        return
            "Used by this website only for necessary system functions such as recording consent to other cookies." .
            "<br>These cookies are only sent to this site when you visit it, and we do not share or retain their data.";
    }

    public static function onCookieExpiration_auth()
    {
        return "60 days";
    }

    public static function onCookieExpiration_system()
    {
        return "7 days";
    }

    public static function onCookieExpiration_ui()
    {
        return "7 days";
    }

    public static function onCookieDescribe_system_cookierules()
    {
        $d = "Stores a list of what cookie types you have authorized to be stored on your computer by this site.";
        return $d;
    }

    public static function onCookieDescribe_auth_session()
    {
        $url = new URL('/~user/authentication_log.html');
        return "Stores an authorization token used to verify that you are signed in as " . Users::current() . '. ' .
            "This cookie is logged and stored in a way that links your account to your IP address, sign-in time, and browser user agent." .
            "To view this stored information visit <a href='$url'>your account's authentication log</a>.";
    }

    public static function onCookieExpiration_PHPSESSID()
    {
        if ($ttl = intval(ini_get('session.cookie_lifetime'))) {
            $interval = new DateInterval("P{$ttl}s");
            return $interval->format("%d days");
        } else {
            return null;
        }
    }

    public static function onCookieDescribe_PHPSESSID()
    {
        if (Config::get('php_session.enabled')) {
            return 'Used by the CMS to store and verify your sign-in status. Once you sign in, on the server this cookie is used to link your account to your IP address, sign-in time, and browser user agent.'
                . ' This cookie and the PHP session store may also be used by third party libraries to manage the UI state of things like forms.';
        }
        $d = "This cookie can be set automatically by the PHP programming language, or it may have been set by a different piece of software running on this server.";
        $d .= " This cookie and the PHP session store are not used directly by any core CMS code, but may be necessary for third party libraries to manage the UI state of things like forms.";
        return $d;
    }

    public static function describe(string $key): string
    {
        @list($type, $name) = explode('/', $key, 2);
        return
            Config::get('cookies.descriptions.' . $key) ??
            Dispatcher::firstValue('onCookieDescribe_' . $key) ??
            Dispatcher::firstValue('onCookieDescribe_' . $type) ??
            Dispatcher::firstValue('onCookieDescribe', [$type, $name]) ??
            "No description set";
    }

    public static function name(string $key): string
    {
        @list($type, $name) = explode('/', $key, 2);
        return
            Config::get('cookies.names.' . $name) ??
            Dispatcher::firstValue('onCookieName_' . $key) ??
            Dispatcher::firstValue('onCookieName_' . $type) ??
            Dispatcher::firstValue('onCookieName', [$type, $name]) ??
            $key;
    }

    public static function expiration(string $key): ?string
    {
        $type = preg_replace('/\/.+$/', '', $key);
        return
            Config::get('cookies.expirations.' . $type) ??
            Dispatcher::firstValue('onCookieExpiration_' . $type) ??
            Dispatcher::firstValue('onCookieExpiration', [$type]) ??
            null;
    }

    public static function allow(string $type)
    {
        $current = static::get('system', 'cookierules') ?? [];
        $current[] = $type;
        $current = array_unique($current);
        static::set('system', 'cookierules', $current, true);
    }

    public static function disallow(string $type)
    {
        $current = static::get('system', 'cookierules') ?? [];
        $current = array_values(array_filter(
            $current,
            function ($e) use ($type) {
                return $type != $e;
            }
        ));
        sort($current);
        static::set('system', 'cookierules', $current, true);
    }

    public static function key(string $type, string $name): string
    {
        return "$type/$name";
    }

    public static function set(string $type, string $name, $value, bool $skipRuleChecks = false, bool $localScope = false)
    {
        $key = static::key($type, $name);
        if ($skipRuleChecks || static::isAllowed($type)) {
            if ($expiration = static::expiration($type)) {
                $expiration = (new DateTime())->add(DateInterval::createFromDateString($expiration));
                $expiration = $expiration->getTimestamp();
            } else {
                $expiration = 0;
            }
            $encoded = json_encode($value);
            $_COOKIE[$key] = $encoded;
            setcookie(
                $key,
                $encoded,
                $expiration,
                static::cookiePath($localScope),
                static::cookieDomain(),
                static::cookieSecure()
            );
            return $value;
        } else {
            throw new CookieRequiredError([$type]);
        }
    }

    /**
     * Undocumented function
     *
     * @param string|array $types
     * @param string $message
     * @return void
     */
    public static function required($types, string $message = '')
    {
        if (!is_array($types)) {
            $types = [$types];
        }
        foreach ($types as $type) {
            if (!static::isAllowed($type)) {
                throw new CookieRequiredError($types, $message);
            }
        }
    }

    public static function unset(string $type, string $name, bool $localScope = false)
    {
        $key = static::key($type, $name);
        unset($_COOKIE[$key]);
        setcookie(
            $key,
            null,
            1,
            static::cookiePath($localScope),
            static::cookieDomain(),
            static::cookieSecure()
        );
    }

    public static function unsetRaw(string $key)
    {
        unset($_COOKIE[$key]);
        setcookie(
            $key,
            null,
            1,
            static::cookiePath(false),
            static::cookieDomain(),
            static::cookieSecure()
        );
        setcookie(
            $key,
            null,
            1
        );
    }

    public static function get(string $type, string $name)
    {
        $key = static::key($type, $name);
        if (isset($_COOKIE[$key])) {
            return json_decode($_COOKIE[$key], true);
        } else {
            return null;
        }
    }

    protected static function cookiePath(bool $localScope): ?string
    {
        if ($localScope) {
            return URLs::sitePath() . Context::url()->pathString();
        } else {
            return URLs::sitePath() ? URLs::sitePath() : '/';
        }
    }

    protected static function cookieDomain(): ?string
    {
        return preg_replace('/:.*$/', '', URLs::siteHost());
    }

    protected static function cookieSecure(): ?bool
    {
        return null;
    }
}
