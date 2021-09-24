<?php

namespace DigraphCMS\Session;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\UI\Forms\Form;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;
use Formward\Fields\Checkbox;

Dispatcher::addSubscriber(Cookies::class);

class Cookies
{
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
        $types = ['system', 'auth', 'csrf'];
        Dispatcher::dispatchEvent('onListCookieTypes', [&$types]);
        return $types;
    }

    public static function form(array $types = null, bool $required = false, bool $skipAllowed = false): Form
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
        $form = new Form("Cookie authorization");
        foreach ($types as $type) {
            $form[$type] = new Checkbox(static::name($type));
            $form[$type]->addTip(static::describe($type), 'description');
            if ($expiration = static::expiration($type)) {
                $form[$type]->addTip("These cookies automatically expire on your computer after $expiration.", 'expiration');
            } else {
                $form[$type]->addTip('These cookies automatically expire on your computer when you close your browser.', 'expiration');
            }
            if ($required) {
                $form[$type]->default(true);
                $form[$type]->required(true);
            } else {
                $form[$type]->default(static::isAllowed($type));
            }
        }
        $form->submitButton()->label('Accept the selected cookies');
        $form->csrf(false);
        $form->addCallback(function () use ($form) {
            foreach ($form as $type => $field) {
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
                        "They store one-time tokens that are used in security checks that prevent attackers from executing actions on your behalf, such as to verify that a form is actually being submitted by you, or to prevent forms from being submitted more than once." .
                        "<br>Please note that for security and performance reasons these cookies will be scoped to only the URL paths where they are needed. " .
                        "This will prevent most CSRF cookies from appearing on the <a href='$url'>current cookies page</a>, because your browser has not been requested to send them there.";
            }
        } else {
            switch ($type) {
                case 'csrf':
                    return "CSRF cookies store one-time tokens that are used to prevent attackers from executing actions on your behalf, such as verifying that a form is being submitted by you.";
            }
        }
        return null;
    }

    public static function onCookieDescribe_system_flashnotifications()
    {
        return "Temporarily stores notifications that need to be displayed on the next page you visit, such as confirmations when an action is performed.";
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
            "Used by this website only for necessary system functions such as recording consent to other cookies and managing UI state." .
            "<br>These cookies are only sent to this site when you visit it, and we do not share or retain their data.";
    }

    public static function onCookieExpiration_auth()
    {
        return "60 days";
    }

    public static function onCookieDescribe_system_cookierules()
    {
        $d = "Stores a list of what cookie types you have authorized to be stored on your computer by this site.";
        return $d;
    }

    public static function onCookieDescribe_auth_session()
    {
        return "Stores an authorization token used to verify that you are signed in as " . Users::current() . '. ' .
            "<strong>Never</strong> share the value of this cookie with anyone. The secret token contained in it should be kept secret as if it were a password, as it could used to hijack your account.";
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
        $d = "This cookie can be set automatically by the PHP programming language, or it may have been set by a different piece of software running on this server.";
        $d .= " This cookie and the PHP session store are not used directly by any core CMS code, but may be necessary for third party libraries to manage UI state of things like forms.";
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
