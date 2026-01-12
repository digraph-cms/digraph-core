<?php

namespace DigraphCMS\Security;

use DigraphCMS\Cache\Cache;
use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\DB\DB;
use DigraphCMS\DB\DBConnectionException;
use DigraphCMS\Digraph;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\Session\Cookies;
use DigraphCMS\Session\Session;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use Envms\FluentPDO\Exception;

class Security
{

    /**
     * Secure this request behind a CAPTCHA if user is flagged, operates by
     * bouncing the user to a dedicated CAPTCHA page then back to this URL.
     *
     * @return void
     * @throws RedirectException
     */
    public static function requireSecurityCheck(): void
    {
        if (!static::flagged())
            return;
        throw new RedirectException(static::captchaUrl(), targetFrame: '_top');
    }

    public static function cronJob_maintenance_heavy(): void
    {
        // clean up expired captcha tokens
        DB::query()
            ->deleteFrom('security_captcha_token')
            ->where('expires < ?', time())
            ->execute();
    }

    public static function captchaUrl(string|null $frame = null): URL
    {
        $url = new URL('/~captcha/');
        $url->setArg('bounce', Context::url()->__toString());
        if ($frame) {
            $url->setArg('frame', $frame);
        }
        return $url;
    }

    /**
     * Check if the current user is flagged in any way, meaning that they should
     * be given a CAPTCHA if required.
     *
     * @return bool
     * @throws Exception
     */
    public static function flagged(bool $guest_default = true): bool
    {
        if (Session::user()) {
            // always respect flags for the authentication/user of users
            if (static::authenticationFlagged() || static::userFlagged()) {
                return true;
            }
            // only respect IP flags if their session has not passed a captcha
            if (static::ipFlagged() && !static::sessionPassed()) {
                return true;
            }
            // users are unflagged by default
            return false;
        }
        else {
            // without authentication, unflag if IP is not flagged or session has passed a captcha
            if (!static::ipFlagged() && !static::sessionPassed()) {
                return false;
            }
            // guests are flagged by default
            return $guest_default;
        }
    }

    /**
     * Unflag the current user, removing any CAPTCHA requirements for the duration specified in Config::get('captcha.pass_ttl')
     *
     * @return void
     * @throws DBConnectionException
     * @throws Exception
     */
    public static function unflag(): void
    {
        static::unflagIP();
        static::unflagAuthentication();
        static::unflagUser();
        static::unflagSession();
    }

    /**
     * Flag the current user for CAPTCHA verification. This should be done if
     * anything strange happens with this user, such as a failed login attempt
     * or any other sort of suspicious activity.
     *
     * @param string $reason
     *
     * @return void
     * @throws Exception
     */
    public static function flag(string $reason): void
    {
        static::flagIP(null, $reason);
        static::flagAuthentication(null, $reason);
        static::flagUser(null, $reason);
        static::flagSession();
    }

    public static function unflagSession(): void
    {
        // set a cookie indicating that the user has passed a captcha
        Cookies::set(
            'security',
            'captcha',
            static::generateCaptchaToken(),
            skipRuleChecks: true,
            saveRawValue: true,
        );
    }

    public static function flagSession(): void
    {
        $cookie = Cookies::get('security', 'captcha', true);
        if (!$cookie)
            return;
        // remove the captcha cookie if it exists
        static::invalidateCaptchaToken($cookie);
        // remove the unflag cookie if it exists
        Cookies::unset('security', 'unflag');
    }

    public static function sessionPassed(): bool
    {
        $token = Cookies::get('security', 'captcha', true);
        if (!$token)
            return false;
        if (!static::validateCaptchaToken($token)) {
            Cookies::unset('security', 'captcha');
            return false;
        }
        else {
            return true;
        }
    }

    protected static function generateCaptchaToken(): string
    {
        $token = substr(Digraph::longUUID() . Digraph::longUUID(), 0, 64);
        $expires = time() + (int) Config::get('captcha.pass_ttl');
        DB::query()
            ->insertInto(
                'security_captcha_token',
                [
                    'token'   => $token,
                    'expires' => $expires,
                ],
            )
            ->execute();
        return $token;
    }

    protected static function invalidateCaptchaToken(string $token): void
    {
        DB::query()
            ->deleteFrom('security_captcha_token')
            ->where('token', $token)
            ->execute();
    }

    protected static function validateCaptchaToken(string $token): bool
    {
        return !!DB::query()
            ->from('security_captcha_token')
            ->where('token', $token)
            ->where('expires > ?', time())
            ->count();
    }

    public static function ipFlagged(string|null $ip = null): bool
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        return static::flaggedIPs()->exists($ip)
            && static::flaggedIPs()->value($ip) != 'passed';
    }

    public static function unflagIP(string|null $ip = null): void
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $data = static::flaggedIPs()->get($ip);
        if (!$data)
            return;
        if ($data->value() == 'passed')
            return;
        $data->setValue('passed');
        $data->update();
    }

    public static function flagIP(string|null $ip = null, string $reason = 'unspecified'): void
    {
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $data = static::flaggedIPs()->get($ip)?->data()->get(null) ?? [];
        $data[] = [
            'reason' => $reason,
            'time'   => time(),
            'url'    => static::actualUrl(),
        ];
        static::flaggedIPs()->set($ip, 'pending', $data);
    }

    public static function banned(string|null $ip = null, string|User|null $user = null): bool
    {
        // bypass bans for signed-in users
        $user = $user ?? Session::uuid();
        if ($user instanceof User)
            $user = $user->uuid();
        if ($user != 'guest')
            return false;
        // check the user's IP for excessive flags
        $ip = $ip ?? $_SERVER['REMOTE_ADDR'];
        $key = 'security/ip_bans/' . md5($ip);
        $window = (int) Config::get('security.ip_bans.window');
        return Cache::get(
            $key,
            function () use ($ip, $window): bool {
                $data = static::flaggedIPs()->get($ip);
                if (!$data)
                    return false;
                $time = $data->updated()->getTimestamp();
                if (time() - $time > $window)
                    return false;
                $flags = $data->data()->get(null) ?? [];
                $limit = (int) Config::get('security.ip_bans.limit');
                if (count($flags) < $limit)
                    return false;
                $count = 0;
                $expiry = time() - $window;
                while ($flag = array_pop($flags)) {
                    if ($flag['time'] > $expiry) {
                        $count++;
                        if ($count >= $limit)
                            return true;
                    }
                    else {
                        break;
                    }
                }
                return false;
            },
            $window / 10
        );
    }

    public static function userFlagged(string|User|null $user = null): bool
    {
        if (is_null($user))
            $user = Session::uuid();
        if ($user instanceof User)
            $user = $user->uuid();
        if ($user == 'guest')
            return false;
        return static::flaggedUsers()->exists($user)
            && static::flaggedUsers()->value($user) != 'passed';
    }

    public static function unflagUser(string|User|null $user = null): void
    {
        if (is_null($user))
            $user = Session::uuid();
        if ($user instanceof User)
            $user = $user->uuid();
        if ($user == 'guest')
            return;
        $data = static::flaggedUsers()->get($user);
        if (!$data)
            return;
        if ($data->value() == 'passed')
            return;
        $data->setValue('passed');
        $data->update();
    }

    public static function flagUser(string|User|null $user = null, string $reason = 'unspecified'): void
    {
        if (is_null($user))
            $user = Session::uuid();
        if ($user instanceof User)
            $user = $user->uuid();
        if ($user == 'guest')
            return;
        $data = static::flaggedUsers()->get($user)?->data()->get(null) ?? [];
        $data[] = [
            'reason' => $reason,
            'time'   => time(),
            'url'    => static::actualUrl(),
        ];
        static::flaggedUsers()->set($user, 'pending', $data);
    }

    public static function authenticationFlagged(string|null $authentication_id = null): bool
    {
        $authentication_id = $authentication_id ?? Session::authentication()?->id();
        if (!$authentication_id)
            return false;
        return static::flaggedAuthentications()->exists($authentication_id)
            && static::flaggedAuthentications()->value($authentication_id) != 'passed';
    }

    public static function unflagAuthentication(string|null $authentication_id = null): void
    {
        $authentication_id = $authentication_id ?? Session::authentication()?->id();
        if (!$authentication_id)
            return;
        $data = static::flaggedAuthentications()->get($authentication_id);
        if (!$data)
            return;
        if ($data->value() == 'passed')
            return;
        $data->setValue('passed');
        $data->update();
    }

    public static function flagAuthentication(string|null $authentication_id = null, string $reason = 'unspecified'): void
    {
        $authentication_id = $authentication_id ?? Session::authentication()?->id();
        if (!$authentication_id)
            return;
        $data = static::flaggedAuthentications()->get($authentication_id)?->data()->get(null) ?? [];
        $data[] = [
            'reason' => $reason,
            'time'   => time(),
            'url'    => static::actualUrl(),
        ];
        static::flaggedAuthentications()->set($authentication_id, 'pending', $data);
    }

    /**
     * @return string get the actual URL of the current request, from outside the CMS
     */
    protected static function actualUrl(): string
    {
        return sprintf(
            '%s://%s%s%s',
            isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            @$_SERVER['QUERY_STRING'] ? '?' . @$_SERVER['QUERY_STRING'] : ''
        );
    }

    protected static function flaggedAuthentications(): DatastoreGroup
    {
        return new DatastoreGroup('security_flags', 'flagged_authentications');
    }

    protected static function flaggedUsers(): DatastoreGroup
    {
        return new DatastoreGroup('security_flags', 'flagged_users');
    }

    protected static function flaggedIPs(): DatastoreGroup
    {
        return new DatastoreGroup('security_flags', 'flagged_ips');
    }

}
