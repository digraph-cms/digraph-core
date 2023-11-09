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
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use DigraphCMS\Users\Users;

Dispatcher::addSubscriber(Cookies::class);

class Cookies
{

    public static function printConsentBanner(): string
    {
        if (!static::get('system', 'consentrejected') && static::consentPromptRequired()) {
            $accept = new SingleButton('Accept', function () {
                static::unset('system', 'consentrejected');
                foreach (static::consentPromptTypes() as $type) {
                    static::allow($type);
                }
                // TODO: record record of affirmative consent, should include what the form looked like
            }, ['button--safe']);
            $reject = new SingleButton('Reject', function () {
                static::set('system', 'consentrejected', true);
                foreach (static::allTypes() as $type) {
                    static::disallow($type);
                }
            }, ['button--inverted', 'button--neutral']);
            echo '<div id="cookie-consent-banner" class="navigation-frame navigation-frame--stateless navigation-frame--hide-if-missing">';
            echo "<div class='cookie-consent-banner__explanation'>";
            echo Templates::render("content/cookie-consent-banner.php");
            echo "</div>";
            echo "<div class='cookie-consent-banner__buttons'>";
            echo $accept;
            echo $reject;
            echo "</div>";
            echo '</div>';
        }
        return '';
    }

    public static function consentPromptRequired(): bool
    {
        foreach (static::consentPromptTypes() as $type) {
            if (!static::isAllowed($type)) {
                return true;
            }
        }
        return false;
    }

    public static function consentPromptTypes(): array
    {
        $config = Config::get('cookies.consent_prompt');
        if ($config === false) {
            return [];
        } elseif ($config === true) {
            return static::allTypes();
        } else {
            return $config;
        }
    }

    /**
     * Returns an array of all the cookies that should be used to key output
     * caching. Main reason is to allow separate caching for different UI
     * states, such as flash notification or color settings.
     *
     * @return array
     */
    public static function cacheMutators(): array
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
     * @return bool
     */
    public static function onException_CookieRequiredError()
    {
        Digraph::buildErrorContent(409.1);
        return true;
    }

    public static function csrfToken($name = 'default'): string
    {
        return
            static::get('csrf', $name) ??
            static::set('csrf', $name, bin2hex(random_bytes(16)));
    }

    public static function allTypes(): array
    {
        $types = ['system', 'ui', 'csrf', 'auth'];
        Dispatcher::dispatchEvent('onListCookieTypes', [&$types]);
        return $types;
    }

    public static function necessaryTypes(): array
    {
        $types = ['system', 'ui', 'csrf', 'auth'];
        Dispatcher::dispatchEvent('onListNecessaryCookieTypes', [&$types]);
        return $types;
    }

    public static function optionalTypes(): array
    {
        return array_diff(static::allTypes(), static::necessaryTypes());
    }

    public static function form(array $types = null, bool $required = false, bool $skipAllowed = false): FormWrapper
    {
        $types = $types ?? static::allTypes();
        if ($skipAllowed) {
            $types = array_filter(
                $types,
                function ($type) {
                    return !Cookies::isAllowed($type);
                }
            );
        }
        $form = new FormWrapper('cookie-authorization');
        $form->setCaptcha(false);
        // $form->addChild("<div class='notification notification--info'>In order to create a GDPR compliant record of affirmative consent, your response to this form will be recorded with the time and your IP address. The record will be associated with your account if you are signed in.</div>");
        $form->button()->setText('Accept the selected cookies');
        $form->token()->setCSRF(false);
        $checkboxes = [];
        $system = static::necessaryTypes();
        foreach ($types as $type) {
            if (in_array($type, $system)) {
                continue;
            }
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
            // TODO: Record affirmative consent, should include what the form looked like
        });
        return $form;
    }

    public static function isAllowed(string $type): bool
    {
        $allowed = static::get('system', 'cookierules') ?? [];
        return in_array($type, static::necessaryTypes()) || in_array($type, $allowed);
    }

    public static function onCookieName(string $name)
    {
        switch ($name) {
            case 'system':
                return 'System cookies';
            case 'auth':
                return 'User authorization cookies';
            case 'csrf':
                return 'CSRF protection cookies';
            case 'ui':
                return 'User interface cookies';
            case 'analytics':
                return 'Analytics cookies';
        }
        return null;
    }

    public static function onCookieDescribe(string $type, ?string $name)
    {
        if (!$name) {
            switch ($type) {
                case 'csrf':
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
        return "UI state cookies, used to keep track of the user interface state and your user interface preferences." .
            "<br>These cookies are only sent to this site when you visit it, and we do not share or retain their data.";
    }

    public static function onCookieDescribe_ui_color()
    {
        return "Stores your preferences for whether the site should appear in light or dark mode, and whether it should attempt to use colorblindness-friendly colors." .
            "<br>This cookie is only sent to this site when you visit it, and we do not share or retain its data.";
    }

    public static function onCookieDescribe_auth()
    {
        $logURL = new URL('/users/profile/authentication_log.html');
        return
            "Used by this website for remembering and verifying the identity of the currently signed in user." .
            "<br>These cookies' contents may be stored and logged for security and troubleshooting purposes. They will be associated with personally identifiable information including the date, time, your public IP address, your browser's user agent string, and your account." .
            " Once you sign in you are able to view these records on <a href='$logURL'>your account's authorization log</a>.";
    }

    public static function onCookieDescribe_analytics()
    {
        return
            "Used by this website for website usage analytics, so that administrators can better understand how visitors interact with the site." .
            "<br>These cookies' contents may be stored, logged, and shared with third party analytics services. They will not be associated with your personally identifiable information. They may be associated with your IP address and browser information, and will be associated with your site usage patterns.";
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

    public static function onCookieExpiration_analytics()
    {
        return "120 days";
    }

    public static function onCookieExpiration_system()
    {
        return "7 days";
    }

    public static function onCookieExpiration_ui()
    {
        return "14 days";
    }

    public static function onCookieDescribe_system_cookierules()
    {
        $d = "Stores a list of what cookie types you have authorized to be stored on your computer by this site.";
        return $d;
    }

    public static function onCookieDescribe_auth_session()
    {
        $url = new URL('/users/profile/authentication_log.html');
        return "Stores an authorization token used to verify that you are signed in as " . Users::current() . '. ' .
            "This cookie is logged and stored in a way that links your account to your IP address, sign-in time, and browser user agent." .
            "To view this stored information visit <a href='$url'>your account's authentication log</a>.";
    }

    public static function onCookieExpiration_PHPSESSID()
    {
        if ($ttl = intval(ini_get('session.cookie_lifetime'))) {
            $d1 = new DateTime();
            $d2 = new DateTime();
            $d2->add(new DateInterval("PT{$ttl}S"));
            return $d2->diff($d1)->format("%d days");
        } else {
            return null;
        }
    }

    public static function onCookieDescribe_PHPSESSID()
    {
        return 'Used by a variety of PHP features to store temporary data about your session on this site.';
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
            "",
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
            "",
            1,
            static::cookiePath(false),
            static::cookieDomain(),
            static::cookieSecure()
        );
        setcookie(
            $key,
            "",
            1
        );
    }

    public static function get(string $type, string $name)
    {
        $key = static::key($type, $name);
        if (isset($_COOKIE[$key])) {
            return json_decode($_COOKIE[$key], true, 512, JSON_THROW_ON_ERROR);
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

    protected static function cookieSecure(): bool
    {
        return false;
    }
}
