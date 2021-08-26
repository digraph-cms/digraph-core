<?php

namespace DigraphCMS\Session;

use DateInterval;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Forms\Form;
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

    public static function listTypes(): array
    {
        $types = ['system', 'auth'];
        Dispatcher::dispatchEvent('onListCookieTypes', [&$types]);
        return $types;
    }

    public static function form(array $types = null, $required = false): Form
    {
        $types = $types ?? static::listTypes();
        $form = new Form("Cookie authorization");
        foreach ($types as $type) {
            $form[$type] = new Checkbox(static::name($type));
            $form[$type]->addTip(static::describe($type), 'description');
            if ($expiration = static::expiration($type)) {
                $form[$type]->addTip('These cookies automatically expire on your computer after ' . $expiration, 'expiration');
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

    public static function onCookieName_auth()
    {
        return "User authorization cookies";
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
            "<br>These cookies' contents may be stored, logged, and for security and logging purposes will be associated with the date, time, your IP address, your browser's user agent string, and your account." .
            "<br>Once you sign in you are able to view these records on <a href='$logURL'>your account's authorization log</a>.";
    }

    public static function onCookieName_system()
    {
        return "Minimum system cookies";
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
        $d = "Stores a list of what cookie types you have authorized to be stored on this site.";
        if (static::get('system', 'cookierules')) {
            $d .= "<br>You have currently authorized the following cookies.";
            $d .= '<ul>';
            foreach (static::get('system', 'cookierules') as $type) {
                $d .= "<li>" . static::name($type) . "</li>";
            }
            $d .= '</ul>';
        }
        return $d;
    }

    public static function onCookieDescribe_auth_session()
    {
        return "Stores an authorization token used to verify that you are signed in as " . Users::current();
    }

    public static function onCookieDescribe_PHPSESSID()
    {
        $d = "<ul>";
        $d .= "<li>Set automatically by the PHP programming language.</li>";
        $d .= "<li>Data associated with this cookie may be saved in various datastores, depending on server configuration.</li>";
        if ($ttl = intval(ini_get('session.cookie_lifetime'))) {
            $interval = new DateInterval("P{$ttl}s");
            $d .= "<li>Saved for " . $interval->format("%d days") . "</li>";
        } else {
            $d .= "<li>Expires when you close your browser</li>";
        }
        $d .= "<li>Not used directly by any core CMS code, but may be necessary for third party libraries to manage UI state of things like forms.</li>";
        $d .= "</ul>";
        return $d;
    }

    public static function describe(string $key): string
    {
        $name = preg_replace('/^.+\/\//', '', $key);
        return
            Config::get('cookies.descriptions.' . $name) ??
            Dispatcher::firstValue('onCookieDescribe_' . $name) ??
            Dispatcher::firstValue('onCookieDescribe', [$name]) ??
            "No description set";
    }

    public static function name(string $key): string
    {
        $name = preg_replace('/^.+\/\//', '', $key);
        return
            Config::get('cookies.names.' . $name) ??
            Dispatcher::firstValue('onCookieName_' . $name) ??
            Dispatcher::firstValue('onCookieName', [$name]) ??
            $key;
    }

    public static function expiration(string $key): ?string
    {
        $type = preg_replace('/^.+\/\//', '', $key);
        $type = preg_replace('/\/.+$/', '', $type);
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
        $current = array_filter(
            $current,
            function ($e) use ($type) {
                return $type != $e;
            }
        );
        static::set('system', 'cookierules', $current, true);
    }

    public static function key(string $type, string $name): string
    {
        return "$type/$name";
    }

    public static function set(string $type, string $name, $value, bool $skipRuleChecks = false)
    {
        $key = static::key($type, $name);
        if ($skipRuleChecks || static::isAllowed($type)) {
            if ($expiration = static::expiration($type)) {
                $expiration = (new DateTime())->add(DateInterval::createFromDateString($expiration));
                $expiration = $expiration->getTimestamp();
            } else {
                $expiration = 0;
            }
            $value = URLs::base64_encode(json_encode($value));
            $_COOKIE[$key] = $value;
            setcookie(
                $key,
                $value,
                $expiration,
                static::cookiePath(),
                static::cookieDomain(),
                static::cookieSecure()
            );
        } else {
            throw new CookieRequiredError($type);
        }
    }

    /**
     * Undocumented function
     *
     * @param string|array $types
     * @param string $message
     * @return void
     */
    public static function require($types, string $message = '')
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

    public static function unset(string $type, string $name)
    {
        $key = static::key($type, $name);
        unset($_COOKIE[$key]);
        setcookie(
            $key,
            null,
            1,
            static::cookiePath(),
            static::cookieDomain(),
            static::cookieSecure()
        );
    }

    public static function get(string $type, string $name)
    {
        $key = static::key($type, $name);
        if (isset($_COOKIE[$key])) {
            return json_decode(URLs::base64_decode($_COOKIE[$key]), true);
        } else {
            return null;
        }
    }

    protected static function cookiePath(): ?string
    {
        return URLs::sitePath();
    }

    protected static function cookieDomain(): ?string
    {
        return URLs::$siteHost;
    }

    protected static function cookieSecure(): ?bool
    {
        return null;
    }
}
